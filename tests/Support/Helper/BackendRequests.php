<?php

declare(strict_types=1);

namespace Tests\Support\Helper;

use Codeception\Module;
use Codeception\Module\WebDriver;

/**
 * Waiting for the Vue.js widgets to have talked to the backend.
 *
 * All widgets perform their changes and their updates using fetch() (see web/js/modules/shared/ApiClient.js)
 * and only re-render once the answer arrives. Waiting for a fixed amount of time instead is either flaky or
 * slow: a REST round trip regularly takes longer than 100ms, and a widget navigated away from before the
 * answer arrived never gets to show the new state at all.
 *
 * To be able to tell if a request is still running, fetch() is wrapped in a counting function - which needs
 * to happen before the request is triggered, and again after every page load, as the counter belongs to the
 * document.
 */
class BackendRequests extends Module
{
    private const COUNTER_JS = 'window.__acceptanceTestPendingRequests';

    public function getWebDriver(): WebDriver
    {
        return $this->getModule(AntragsgruenWebDriver::class);
    }

    /**
     * Runs JavaScript that triggers one or more backend requests and waits until none of them is running
     * anymore. Regular polling requests are counted as well; as they are answered within milliseconds, this
     * only means that the wait might take one round trip longer than the triggered request itself.
     */
    public function executeJSAndWaitForBackend(string $js, int $timeoutSeconds = 10): void
    {
        $this->installRequestCounter();
        $this->getWebDriver()->executeJS($js);
        $this->waitForBackendRequests($timeoutSeconds);
    }

    public function waitForBackendRequests(int $timeoutSeconds = 10): void
    {
        $this->installRequestCounter();
        $this->getWebDriver()->waitForJS('return ' . self::COUNTER_JS . ' === 0;', $timeoutSeconds);
    }

    private function installRequestCounter(): void
    {
        $this->getWebDriver()->executeJS(
            'if (!window.__acceptanceTestCounterInstalled) {' .
            '    window.__acceptanceTestCounterInstalled = true;' .
            '    ' . self::COUNTER_JS . ' = 0;' .
            '    const originalFetch = window.fetch;' .
            '    window.fetch = function () {' .
            '        ' . self::COUNTER_JS . '++;' .
            '        return originalFetch.apply(this, arguments).then(function (response) {' .
            // The widget only renders once it has read the body, so the request does not count as
            // finished as long as the body is still on its way. Reading a clone leaves the response
            // itself untouched for the widget.
            '            return response.clone().arrayBuffer().then(function () { return response; });' .
            '        }).finally(function () {' .
            '            ' . self::COUNTER_JS . '--;' .
            '        });' .
            '    };' .
            '}'
        );
    }
}
