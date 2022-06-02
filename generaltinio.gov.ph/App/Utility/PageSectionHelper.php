<?php
namespace App\Utility;

use Jawira\CaseConverter\Convert;
use Kaoken\MarkdownIt\MarkdownIt;

class PageSectionHelper {
  static function extractLinksFrom($sections) {
    foreach($sections as $section) {
      $links[] = [
        'url'   => '#' . (new Convert($section->title))->toKebab(),
        'title' => $section->title
      ];
    }

    return $links;
  }

  static function extractSectionsFrom($sections) {
    $md = new MarkdownIt(['html'=> true]);
    
    foreach($sections as $section) {
      $page_sections[] = $md->render(
        implode("\n\n", [
          '<h1 class="py-4 text-3xl border-b font-semibold" id="' . (new Convert($section->title))->toKebab() . '">' . $section->title . '</h1>',
          $section->content,
        ])
      );
    }
    
    return $page_sections;
  }
}

