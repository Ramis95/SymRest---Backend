<?php
/**
 * Created by PhpStorm.
 * User: irami
 * Date: 03.04.2020
 * Time: 15:00
 */

namespace App\Services;


class LanguageController
{

    private $selected_language = 'ru';

    public function selected()
    {
        return $this->selected_language;
    }

}