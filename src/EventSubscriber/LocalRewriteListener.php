<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class LocaleRewriteSubscriber implements EventSubscriberInterface
{
    private $defaultLocale;
    private $supportedLocales;

    /**
     * $defaultLocale and $supportedLocales injected from services.yaml
     */
    public function __construct(string $defaultLocale, string $supportedLocales)
    {
        $this->defaultLocale = $defaultLocale;
        $this->supportedLocales = explode("|", $supportedLocales);
    }

    public function onKernelRequest(RequestEvent $event)
    {
        $request = $event->getRequest();
        $oldUrl = $request->getPathInfo();
        $exploded = explode("/", $oldUrl);
        $locale = $request->getSession()->get("_locale", $this->defaultLocale);
        $newUrl = null;
        
        if(!in_array($exploded[1], $this->supportedLocales))
        {  // If no prefix or prefix not found in supported locales
            $newUrl = "/" . $locale . $oldUrl;
        }
        else
        {   // If prefix found in supported locales but different from actual
            if($exploded[1] !== $locale){
                $exploded[1] = $locale;
                $implode = implode("/", $exploded);
                $newUrl = $implode;
            }
        }

        if($newUrl){
            $event->setResponse(new RedirectResponse($newUrl));
        }
    }

    public static function getSubscribedEvents()
    {
        return array(
            // must be registered before the default Locale listener
            KernelEvents::REQUEST => [['onKernelRequest', 101]],
        );
    }
}