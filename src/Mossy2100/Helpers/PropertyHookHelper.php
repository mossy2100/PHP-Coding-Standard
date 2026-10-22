<?php

declare(strict_types=1);

namespace Mossy2100\Helpers;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use WeakReference;

/**
 * Helper class for detecting PHP 8.4 property hooks.
 *
 * Property hooks have the syntax:
 *   public Type $propertyName {
 *       get { ... }
 *       set { ... }
 *   }
 *
 * Or with arrow functions:
 *   public Type $propertyName {
 *       get => expression;
 *       set => expression;
 *   }
 *
 * PHP_CodeSniffer's tokenizer doesn't recognise property hook braces as scope openers, so the sniffs that need to
 * know about hooks get them from findHookScopes().
 */
class PropertyHookHelper
{
    /**
     * Tokens that can come directly before a property's variable: modifiers and type declaration tokens.
     *
     * @var array<int|string, true>
     */
    private const array PROPERTY_PREFIX_TOKENS = [
        T_PUBLIC                 => true,
        T_PROTECTED              => true,
        T_PRIVATE                => true,
        T_STATIC                 => true,
        T_READONLY               => true,
        T_VAR                    => true,
        T_STRING                 => true,
        T_ARRAY                  => true,
        T_CALLABLE               => true,
        T_SELF                   => true,
        T_PARENT                 => true,
        T_NULLABLE               => true,
        T_TYPE_UNION             => true,
        T_TYPE_INTERSECTION      => true,
        T_TYPE_CLOSE_PARENTHESIS => true,
        T_NS_SEPARATOR           => true,
        T_NAME_FULLY_QUALIFIED   => true,
        T_NAME_QUALIFIED         => true,
        T_NAME_RELATIVE          => true,
        T_FALSE                  => true,
        T_TRUE                   => true,
        T_NULL                   => true,
        T_PUBLIC_SET             => true,
        T_PROTECTED_SET          => true,
        T_PRIVATE_SET            => true,
    ];

    /**
     * Modifiers that make a constructor parameter a promoted property.
     *
     * @var list<int|string>
     */
    private const array PROMOTION_MODIFIERS = [
        T_PUBLIC,        T_PROTECTED,     T_PRIVATE,       T_READONLY,      T_PUBLIC_SET,    T_PROTECTED_SET,
        T_PRIVATE_SET,
    ];

    /**
     * The file whose hook scopes are cached, for isInsidePropertyHook().
     *
     * @var ?WeakReference<File>
     */
    private static ?WeakReference $cachedFile = null;

    /**
     * The last token position isInsidePropertyHook() was called with.
     */
    private static int $cachedStackPtr = -1;

    /**
     * The cached hook scopes, for isInsidePropertyHook().
     *
     * @var array<int, int>
     */
    private static array $cachedScopes = [];

    /**
     * Check if a token is inside a property hook, i.e. between the braces of a property's hook list.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the token to check.
     * @return bool True if the token is inside a property hook.
     */
    public static function isInsidePropertyHook(File $phpcsFile, int $stackPtr): bool
    {
        // Scanning the file is O(n), and this is called for every member variable, so cache the scopes. PHPCS visits
        // tokens in order within a run, so a pointer that doesn't move forward means a new run (e.g. a phpcbf pass
        // after the file was changed), and the cache is rebuilt.
        if (self::$cachedFile?->get() !== $phpcsFile || $stackPtr <= self::$cachedStackPtr) {
            self::$cachedFile = WeakReference::create($phpcsFile);
            self::$cachedScopes = self::findHookScopes($phpcsFile);
        }
        self::$cachedStackPtr = $stackPtr;

        foreach (self::$cachedScopes as $opener => $closer) {
            if ($opener < $stackPtr && $stackPtr < $closer) {
                return true;
            }
        }

        return false;
    }

    /**
     * Build a map of property hook opener to closer positions.
     *
     * The map includes both the hook list braces (`{ get ... set ... }`) and the braces of each hook body
     * (`get { ... }`). It works by finding property declarations and looking forward for hooks, rather than looking
     * at each brace and trying to determine if it's a hook.
     *
     * @param File $phpcsFile The file being scanned.
     * @return array<int, int> Map of opener => closer positions.
     */
    public static function findHookScopes(File $phpcsFile): array
    {
        $tokens = $phpcsFile->getTokens();
        $scopes = [];

        for ($i = 0; $i < $phpcsFile->numTokens; $i++) {
            if (!self::isPropertyVariable($phpcsFile, $i)) {
                continue;
            }

            $opener = self::findHookListOpener($phpcsFile, $i);
            if ($opener === false) {
                continue;
            }

            $closer = self::findMatchingCloseBrace($phpcsFile, $opener);
            if ($closer !== false) {
                $scopes[$opener] = $closer;

                // Now scan inside the hook list for get/set block bodies.
                self::findHookBodies($phpcsFile, $opener, $closer, $scopes);
            }
        }

        return $scopes;
    }

    /**
     * Find the opening brace of a property's hook list.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $varPtr The position of the property's T_VARIABLE token.
     * @return int|false The position of the hook list's opening brace, or false if the property has no hooks.
     */
    public static function findHookListOpener(File $phpcsFile, int $varPtr): int|false
    {
        $tokens = $phpcsFile->getTokens();
        $depth = 0;

        // Look forward for { or ;, skipping past any default value assignment.
        for ($next = $varPtr + 1; $next < $phpcsFile->numTokens; $next++) {
            $code = $tokens[$next]['code'];

            // Track nesting.
            if ($code === T_OPEN_PARENTHESIS || $code === T_OPEN_SQUARE_BRACKET || $code === T_OPEN_SHORT_ARRAY) {
                $depth++;
            } elseif (
                $code === T_CLOSE_PARENTHESIS
                || $code === T_CLOSE_SQUARE_BRACKET
                || $code === T_CLOSE_SHORT_ARRAY
            ) {
                $depth--;
            }

            // Found semicolon - no hooks. For a promoted parameter, a comma or the closing parenthesis of the
            // parameter list also means no hooks.
            if ($depth < 0 || ($depth === 0 && ($code === T_SEMICOLON || $code === T_COMMA))) {
                return false;
            }

            // Found opening brace at base level - this is the hook list opener.
            if ($depth === 0 && $code === T_OPEN_CURLY_BRACKET) {
                return $next;
            }
        }

        return false;
    }

    /**
     * Check whether a token is the variable of a property declaration or a promoted constructor parameter.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the token to check.
     * @return bool True if the token is a property's variable.
     */
    public static function isPropertyVariable(File $phpcsFile, int $stackPtr): bool
    {
        $tokens = $phpcsFile->getTokens();

        if ($tokens[$stackPtr]['code'] !== T_VARIABLE) {
            return false;
        }

        // Must be directly inside a class, trait or interface, not in a method body.
        $conditions = $tokens[$stackPtr]['conditions'];
        if ($conditions === [] || !isset(Tokens::OO_SCOPE_TOKENS[end($conditions)])) {
            return false;
        }

        // Inside parentheses, only a promoted constructor parameter is a property. Without this check, a typed
        // method parameter would match, and the forward scan could reach an unrelated brace such as a closure's.
        if (isset($tokens[$stackPtr]['nested_parenthesis']) && !self::isPromotedParameter($phpcsFile, $stackPtr)) {
            return false;
        }

        $prev = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($stackPtr - 1), null, true);
        return $prev !== false && isset(self::PROPERTY_PREFIX_TOKENS[$tokens[$prev]['code']]);
    }

    /**
     * Check whether a variable inside parentheses is a promoted constructor parameter, i.e. whether its parameter
     * declaration includes a visibility or readonly modifier.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $varPtr The position of the T_VARIABLE token.
     * @return bool True if the variable is a promoted parameter.
     */
    public static function isPromotedParameter(File $phpcsFile, int $varPtr): bool
    {
        $tokens = $phpcsFile->getTokens();
        $nestedParenthesis = $tokens[$varPtr]['nested_parenthesis'];
        $opener = array_key_last($nestedParenthesis);

        // Find the start of this parameter: the previous comma at the same nesting level, or the opening parenthesis.
        $start = $opener;
        for ($j = ($varPtr - 1); $j > $opener; $j--) {
            if ($tokens[$j]['code'] === T_COMMA && ($tokens[$j]['nested_parenthesis'] ?? []) === $nestedParenthesis) {
                $start = $j;
                break;
            }
        }

        return $phpcsFile->findPrevious(self::PROMOTION_MODIFIERS, ($varPtr - 1), $start) !== false;
    }

    /**
     * Find the matching close brace for an open brace.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $openPtr Position of the opening brace.
     * @return int|false Position of the closing brace, or false if not found.
     */
    private static function findMatchingCloseBrace(File $phpcsFile, int $openPtr): int|false
    {
        $tokens = $phpcsFile->getTokens();
        $depth = 1;

        for ($i = ($openPtr + 1); $i < $phpcsFile->numTokens; $i++) {
            if ($tokens[$i]['code'] === T_OPEN_CURLY_BRACKET) {
                $depth++;
            } elseif ($tokens[$i]['code'] === T_CLOSE_CURLY_BRACKET) {
                $depth--;
                if ($depth === 0) {
                    return $i;
                }
            }
        }

        return false;
    }

    /**
     * Find get/set block bodies inside a property hook list.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $containerOpener Position of the hook list's opening brace.
     * @param int $containerCloser Position of the hook list's closing brace.
     * @param array<int, int> $scopes Reference to the scopes array to populate.
     */
    private static function findHookBodies(
        File $phpcsFile,
        int $containerOpener,
        int $containerCloser,
        array &$scopes
    ): void {
        $tokens = $phpcsFile->getTokens();

        for ($i = ($containerOpener + 1); $i < $containerCloser; $i++) {
            // Look for 'get' or 'set' keywords.
            if ($tokens[$i]['code'] !== T_STRING) {
                continue;
            }

            $content = strtolower($tokens[$i]['content']);
            if ($content !== 'get' && $content !== 'set') {
                continue;
            }

            // Look for opening brace after get/set (skipping whitespace, parameters, return type).
            $next = ($i + 1);
            $parenDepth = 0;

            while ($next < $containerCloser) {
                $code = $tokens[$next]['code'];

                // Track parentheses (for set($value) parameters).
                if ($code === T_OPEN_PARENTHESIS) {
                    $parenDepth++;
                } elseif ($code === T_CLOSE_PARENTHESIS) {
                    $parenDepth--;
                }

                // Found arrow function syntax (get => ...) - no block body.
                if ($parenDepth === 0 && $code === T_DOUBLE_ARROW) {
                    break;
                }

                // Found opening brace - this is a hook body.
                if ($parenDepth === 0 && $code === T_OPEN_CURLY_BRACKET) {
                    $bodyCloser = self::findMatchingCloseBrace($phpcsFile, $next);
                    if ($bodyCloser !== false) {
                        $scopes[$next] = $bodyCloser;
                    }
                    // Skip past this hook body.
                    $i = $bodyCloser !== false ? $bodyCloser : $next;
                    break;
                }

                // Found semicolon - end of arrow syntax.
                if ($code === T_SEMICOLON) {
                    break;
                }

                $next++;
            }
        }
    }
}
