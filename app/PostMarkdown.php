<?php

namespace App;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\MarkdownConverter;

class PostMarkdown
{
    public static function render(string $content): string
    {
        $environment = new Environment(['html_input' => 'strip', 'allow_unsafe_links' => false, 'max_nesting_level' => 32]);
        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addEventListener(DocumentParsedEvent::class, function (DocumentParsedEvent $event): void {
            $walker = $event->getDocument()->walker();
            while ($nodeEvent = $walker->next()) {
                $node = $nodeEvent->getNode();
                if ($nodeEvent->isEntering() && $node instanceof Heading) {
                    $node->setLevel(max(2, min(3, $node->getLevel())));
                }
            }
        });

        return (string) (new MarkdownConverter($environment))->convert($content);
    }
}
