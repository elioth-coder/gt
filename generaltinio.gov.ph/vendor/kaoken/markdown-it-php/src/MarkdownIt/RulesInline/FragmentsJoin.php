<?php
// Clean up $tokens after emphasis and strikethrough postprocessing:
// merge adjacent text nodes into one and re-calculate all token levels
//
// This is necessary because initially emphasis delimiter markers (*, _, ~)
// are treated as their own separate text $tokens-> Then emphasis rule either
// leaves them as text (needed to merge with adjacent text) or turns them
// into opening/closing tags (which messes up levels inside)->
//
namespace Kaoken\MarkdownIt\RulesInline;

use Kaoken\MarkdownIt\RulesInline\StateInline;

class FragmentsJoin
{
    /**
     * @param \Kaoken\MarkdownIt\RulesInline\StateInline $state
     * @return void
     */
    public function fragmentsJoin(StateInline &$state): void
    {
        $level = 0;
        $tokens = &$state->tokens;
        $max = count($state->tokens);
        
        for ($curr = $last = 0; $curr < $max; $curr++) {
            // re-calculate levels after emphasis/strikethrough turns some text nodes
            // into opening/closing tags
            if ($tokens[$curr]->nesting < 0) $level--; // closing tag
            $tokens[$curr]->level = $level;
            if ($tokens[$curr]->nesting > 0) $level++; // opening tag
            
            if (
                $tokens[$curr]->type === 'text' &&
                $curr + 1 < $max &&
                $tokens[$curr + 1]->type === 'text'
            ) {

                // collapse two adjacent text nodes
                $tokens[$curr + 1]->content = $tokens[$curr]->content . $tokens[$curr + 1]->content;
            } else {
                if ($curr !== $last) { $tokens[$last] = $tokens[$curr]; }

                $last++;
            }
        }
        
        if ($curr !== $last) {
//            tokens.length = last;
            $state->md->utils->resizeArray($state->tokens, $last);
        }
    }
}