<?php

/**
 * webtrees: online genealogy
 * Copyright (C) 2025 webtrees development team
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Fisharebest\Webtrees\Http\RequestHandlers;

use Fisharebest\Webtrees\Registry;
use Fisharebest\Webtrees\Services\MediaFileService;
use Fisharebest\Webtrees\Services\PendingChangesService;
use Fisharebest\Webtrees\Validator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Create a new media object.
 */
class CreateMediaObjectFromFile implements RequestHandlerInterface
{
    private MediaFileService $media_file_service;

    private PendingChangesService $pending_changes_service;

    /**
     * @param MediaFileService      $media_file_service
     * @param PendingChangesService $pending_changes_service
     */
    public function __construct(MediaFileService $media_file_service, PendingChangesService $pending_changes_service)
    {
        $this->media_file_service      = $media_file_service;
        $this->pending_changes_service = $pending_changes_service;
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $tree  = Validator::attributes($request)->tree();
        $file  = Validator::parsedBody($request)->string('file');
        $type  = Validator::parsedBody($request)->string('type');
        $title = Validator::parsedBody($request)->string('title');
        $note  = Validator::parsedBody($request)->string('note');

        $file  = Registry::elementFactory()->make('OBJE:FILE')->canonical($file);
        $note  = Registry::elementFactory()->make('OBJE:NOTE')->canonical($note);
        $type  = Registry::elementFactory()->make('OBJE:FILE:FORM:TYPE')->canonical($type);
        $title = Registry::elementFactory()->make('OBJE:FILE:TITL')->canonical($title);

        $gedcom = "0 @@ OBJE\n" . $this->media_file_service->createMediaFileGedcom($file, $type, $title, $note);

        $record = $tree->createRecord($gedcom);

        // Accept the new record.  Rejecting it would leave the filesystem out-of-sync with the genealogy
        $this->pending_changes_service->acceptRecord($record);

        return redirect($record->url());
    }
}
