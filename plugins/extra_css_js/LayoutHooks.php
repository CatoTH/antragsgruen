<?php

declare(strict_types=1);

namespace app\plugins\extra_css_js;

use app\models\layoutHooks\Hooks;

class LayoutHooks extends Hooks
{
    private function getSiteAssetPath(): string
    {
        if ($this->consultation && $this->consultation->site) {
            return __DIR__ . '/assets/' . $this->consultation->site->subdomain;
        } else {
            return __DIR__ . '/assets/default';
        }
    }

    private function getConsultationAssetPath(): string
    {
        if ($this->consultation && $this->consultation->site) {
            return __DIR__ . '/assets/' . $this->consultation->site->subdomain . '/' . $this->consultation->urlPath;
        } else {
            return __DIR__ . '/assets/default';
        }
    }

    public function endOfHead(string $before): string
    {
        if (file_exists($this->getSiteAssetPath() . '.css')) {
            $before .= '<style>' . file_get_contents($this->getSiteAssetPath() . '.css') . '</style>';
        }

        if (file_exists($this->getConsultationAssetPath() . '.css')) {
            $before .= '<style>' . file_get_contents($this->getConsultationAssetPath() . '.css') . '</style>';
        }

        return $before;
    }

    public function endPage(string $before): string
    {
        if (file_exists($this->getSiteAssetPath() . '.js')) {
            $this->layout->addOnLoadJS((string)file_get_contents($this->getSiteAssetPath() . '.js'));
        }

        if (file_exists($this->getConsultationAssetPath() . '.js')) {
            $this->layout->addOnLoadJS((string)file_get_contents($this->getConsultationAssetPath() . '.js'));
        }

        return $before;
    }
}
