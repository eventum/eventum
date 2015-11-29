<?php

namespace Eventum\Controller;

use Auth;
use Attachment;
use Issue;

class DownloadController extends BaseController
{
    /** @var string */
    private $cat;

    /** @var int */
    private $iaf_id;

    /** @var int */
    private $usr_id;

    /** @var bool */
    private $force_inline;

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $request = $this->getRequest();

        $this->cat = $request->query->getAlpha('cat');
        $this->iaf_id = $request->query->getInt('id');
        $this->force_inline = $request->query->getBoolean('force_inline');
    }

    /**
     * @inheritdoc
     */
    protected function canAccess()
    {
        Auth::checkAuthentication();

        $this->usr_id = Auth::getUserID();

        return true;
    }

    /**
     * @inheritdoc
     */
    protected function defaultAction()
    {
        if (stristr(APP_BASE_URL, 'https:')) {
            // fix for IE 5.5/6 with SSL sites
            header('Pragma: cache');
        }
        // fix for IE6 (KB812935)
        header('Cache-Control: must-revalidate');

        if ($this->cat == 'attachment') {
            $this->attachmentAction();
        }
    }

    private function attachmentAction()
    {
        $file = Attachment::getDetails($this->iaf_id);
        if (!$file) {
            return;
        }

        if (!Issue::canAccess($file['iat_iss_id'], $this->usr_id)) {
            $this->error(ev_gettext('No access to requested attachment'));
        }

        Attachment::outputDownload(
            $file['iaf_file'], $file['iaf_filename'], $file['iaf_filesize'],
            $file['iaf_filetype'], $this->force_inline
        );
        exit;
    }

    /**
     * @inheritdoc
     */
    protected function prepareTemplate()
    {
    }
}
