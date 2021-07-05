<?php

// src/EventSubscriber/LocaleSubscriber.php
namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\RedirectResponse;

class LocaleSubscriber implements EventSubscriberInterface
{
    private $defaultLocale;
    private $supportedLocales;

    public function __construct(string $defaultLocale, string $supportedLocales)
    {
        $this->defaultLocale = $defaultLocale;
        $this->supportedLocales = explode("|", $supportedLocales);
    }

    public function onKernelRequest(RequestEvent $event)
    {
        $request = $event->getRequest();
        if (!$request->hasPreviousSession()) {
            return;
        }
        $oldUrl = $request->getPathInfo();
        $exploded = explode("/", $oldUrl);
        $locale = $request->attributes->get('_locale');
        $session_locale = $request->getSession()->get('_locale', $this->defaultLocale);        
        
        if($locale && ($session_locale || !in_array($locale, $this->supportedLocales)))
        {  // If no prefix or prefix not found in supported locales
            if($locale != $session_locale)
                $event->setResponse(new RedirectResponse("/" . $session_locale . "/" . $exploded[2]));
        }

        // try to see if the locale has been set as a _locale routing parameter
        if ($locale = $request->attributes->get('_locale')) {
            $request->getSession()->set('_locale', $session_locale);
        } else {
            // if no explicit locale has been set on this request, use one from the session
            $request->setLocale($request->getSession()->get('_locale', $this->defaultLocale));
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            // must be registered before (i.e. with a higher priority than) the default Locale listener
            KernelEvents::REQUEST => [['onKernelRequest', 20]],
        ];
    }
}

