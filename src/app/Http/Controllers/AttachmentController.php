<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\Indent;
use App\Models\IndentParticipant;
use App\Rules\ValidAttachmentRule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    /**
     * Store one or more attachments directly to an indent from the indent route.
     * Accessible by authorized persons (Creator, Current Holder, Participants, Admins).
     */
    public function store(Request $request, Indent $indent): RedirectResponse
    {
        // Enforce authorization policy (403 for unauthorized users)
        Gate::authorize('addAttachment', $indent);

        $request->validate([
            'attachments' => ['required', 'array', 'min:1', 'max:20'],
            'attachments.*' => ['required', 'file', new ValidAttachmentRule()],
        ]);

        $user = $request->user();
        $count = 0;

        foreach ($request->file('attachments') as $file) {
            if (! $file || ! $file->isValid()) {
                continue;
            }

            $originalName = $file->getClientOriginalName();
            $size = $file->getSize();
            $mime = $file->getMimeType() ?: $file->getClientMimeType() ?: 'application/octet-stream';

            // Store strictly on private filesystem disk under indents/{indent_id}/
            $storedPath = $file->store("indents/{$indent->id}", 'private');

            Attachment::create([
                'indent_id' => $indent->id,
                'movement_id' => $indent->movements()->latest()->first()?->id,
                'uploader' => $user->cpf,
                'original_name' => $originalName,
                'stored_path' => $storedPath,
                'size' => $size,
                'mime_type' => $mime,
            ]);

            $count++;
        }

        // Register uploader as workflow participant if not already registered
        IndentParticipant::firstOrCreate([
            'indent_id' => $indent->id,
            'cpf' => $user->cpf,
        ]);

        return redirect()
            ->route('indents.show', $indent)
            ->with('status', "{$count} file(s) attached successfully to indent #{$indent->number}.");
    }

    /**
     * Download an attachment via streamed response after checking chain permissions.
     * Enforces that users outside the chain get 403 Forbidden.
     */
    public function download(Attachment $attachment): StreamedResponse
    {
        // Enforce authorization policy (403 for users outside the chain)
        Gate::authorize('view', $attachment);

        $disk = Storage::disk('private');

        if (! $disk->exists($attachment->stored_path)) {
            // Also fallback check on local disk root if needed
            if (! Storage::disk('local')->exists($attachment->stored_path)) {
                abort(404, 'The requested file could not be found on the private storage disk.');
            }
            $disk = Storage::disk('local');
        }

        // Stream the download directly from private storage to avoid loading entire file into memory
        return $disk->download(
            $attachment->stored_path,
            $attachment->original_name,
            [
                'Content-Type' => $attachment->mime_type ?: 'application/octet-stream',
                'Content-Length' => (string) $attachment->size,
                'Cache-Control' => 'no-cache, private',
            ]
        );
    }

    /**
     * Remove an attachment from storage and database (only allowed while indent is a draft).
     */
    public function destroy(Attachment $attachment): RedirectResponse
    {
        Gate::authorize('delete', $attachment);

        $name = $attachment->original_name;

        // Delete physical file from private disk
        if (Storage::disk('private')->exists($attachment->stored_path)) {
            Storage::disk('private')->delete($attachment->stored_path);
        }

        $attachment->delete();

        return back()->with('status', "Attachment '{$name}' has been deleted.");
    }
}
