<?php

namespace App\Phphub\Presenters;

use Laracasts\Presenter\Presenter;

class SitePresenter extends Presenter
{
    public function linkWithUTMSource()
    {
        $append = 'utm_source=phphub.org';

        return strpos($this->link, '?') === false
            ? $this->link . '?' . $append
            : $this->link . '&' . $append;
    }

    public function icon($size = 40)
    {
        if (!$this->favicon) {
            return cdn('/assets/images/emoji/arrow_right.png');
        }

        // $path = '/uploads/sites/' . $this->favicon;
        $path = $this->favicon;
        if (strpos($path, '.ico') === false) {
            return cdn($path) . "?imageView2/1/w/{$size}/h/{$size}";
        } else {
            return cdn($path);
        }
    }
}
