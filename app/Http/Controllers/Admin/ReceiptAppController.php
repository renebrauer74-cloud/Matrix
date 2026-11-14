<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Receipts\LoadReceiptAppFileAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

/**
 * Quittungs-App für Tablet/Handy – nur für angemeldete Admin-Benutzer.
 */
class ReceiptAppController extends Controller
{
    public function show(LoadReceiptAppFileAction $loadFile): Response
    {
        return $this->file('index.html', $loadFile);
    }

    public function file(string $path, LoadReceiptAppFileAction $loadFile): Response
    {
        $file = $loadFile->execute($path, route('admin.receipts.show'));

        abort_if($file === null, 404);

        return response($file['content'], 200, $file['headers']);
    }
}
