<?php

/**
 * This file is part of the mo4-coding-standard (phpcs standard)
 *
 * PHP version 5
 *
 * @category PHP
 * @package  PHP_CodeSniffer-MO4
 * @author   Xaver Loppenstedt <xaver@loppenstedt.de>
 * @license  http://spdx.org/licenses/MIT MIT License
 * @version  GIT: master
 * @link     https://github.com/Mayflower/mo4-coding-standard
 */

/**
 * Unnecessary Namespace Usage sniff.
 *
 * Full namespace declaration should be skipped in favour of the short declaration.
 *
 * @category  PHP
 * @package   PHP_CodeSniffer-MO4
 * @author    Xaver Loppenstedt <xaver@loppenstedt.de>
 * @author    Marco Jantke <marco.jantke@gmail.com>
 * @author    Steffen Ritter <steffenritter1@gmail.com>
 * @copyright 2013 Xaver Loppenstedt, some rights reserved.
 * @license   http://spdx.org/licenses/MIT MIT License
 * @link      https://github.com/Mayflower/mo4-coding-standard
 */
class MO4_Sniffs_Formatting_UnnecessaryNamespaceUsageSniff
    implements PHP_CodeSniffer_Sniff
{

    /**
     * Tokens used in full class name.
     * @var array
     */
    private $_classNameTokens = array(
                                 T_NS_SEPARATOR,
                                 T_STRING,
                                );


    /**
     * Registers the tokens that this sniff wants to listen for.
     *
     * @return array(int)
     * @see    Tokens.php
     */
    public function register()
    {
        return array(T_CLASS);

    }//end register()


    /**
     * Called when one of the token types that this sniff is listening for
     * is found.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The PHP_CodeSniffer file where the
     *                                        token was found.
     * @param int                  $stackPtr  The position in the PHP_CodeSniffer
     *                                        file's token stack where the token
     *                                        was found.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $docCommentTags = array(
                           '@param'  => 1,
                           '@return' => 1,
                           '@throws' => 1,
                           '@var'    => 2,
                          );
        $scanTokens     = array(
                           T_NS_SEPARATOR,
                           T_DOC_COMMENT_OPEN_TAG,
                          );

        $tokens        = $phpcsFile->getTokens();
        $useStatements = $this->getUseStatements($phpcsFile, 0, ($stackPtr - 1));
        $nameSpace     = $this->getNameSpace($phpcsFile, 0, ($stackPtr - 1));

        $nsSep = $phpcsFile->findNext($scanTokens, ($stackPtr + 1));

        while ($nsSep !== false) {
            $classNameEnd = $phpcsFile->findNext(
                $this->_classNameTokens,
                $nsSep,
                null,
                true
            );

            if ($tokens[$nsSep]['code'] === T_NS_SEPARATOR) {
                if ($tokens[($nsSep - 1)]['code'] === T_STRING) {
                    $nsSep -= 1;
                }

                $className = $phpcsFile->getTokensAsString(
                    $nsSep,
                    ($classNameEnd - $nsSep)
                );

                $this->_checkShorthandPossible(
                    $phpcsFile,
                    $useStatements,
                    $className,
                    $nameSpace,
                    $nsSep,
                    ($classNameEnd - 1),
                    false
                );
            } else {
                // Doc comment block.
                foreach ($tokens[$nsSep]['comment_tags'] as $tag) {
                    $content = $tokens[$tag]['content'];
                    if ((array_key_exists($content, $docCommentTags)) === false) {
                        continue;
                    }

                    $next    = ($tag + 1);
                    $lineEnd = $phpcsFile->findNext(
                        array(
                         T_DOC_COMMENT_CLOSE_TAG,
                         T_DOC_COMMENT_STAR,
                        ),
                        $next
                    );

                    $docCommentStringPtr = $phpcsFile->findNext(
                        T_DOC_COMMENT_STRING,
                        $next,
                        $lineEnd
                    );

                    if ($docCommentStringPtr === false) {
                        continue;
                    }

                    $docLine = $tokens[$docCommentStringPtr]['content'];

                    $docLineTokens = preg_split(
                        '/\s+/',
                        $docLine,
                        -1,
                        PREG_SPLIT_NO_EMPTY
                    );
                    $docLineTokens = array_slice(
                        $docLineTokens,
                        0,
                        $docCommentTags[$content]
                    );
                    foreach ($docLineTokens as $docLineToken) {
                        $typeTokens = preg_split(
                            '/\|/',
                            $docLineToken,
                            -1,
                            PREG_SPLIT_NO_EMPTY
                        );
                        foreach ($typeTokens as $typeToken) {
                            if (true === in_array($typeToken, $useStatements)) {
                                continue;
                            }

                            $this->_checkShorthandPossible(
                                $phpcsFile,
                                $useStatements,
                                $typeToken,
                                $nameSpace,
                                $docCommentStringPtr,
                                $docCommentStringPtr,
                                true
                            );
                        }//end foreach
                    }//end foreach
                }//end foreach
            }//end if

            $nsSep = $phpcsFile->findNext($scanTokens, ($classNameEnd + 1));
        }//end while

    }//end process()


    /**
     * Get all use statements in range
     *
     * @param PHP_CodeSniffer_File $phpcsFile PHP CS File
     * @param int                  $start     start pointer
     * @param int                  $end       end pointer
     *
     * @return array
     */
    protected function getUseStatements(
        PHP_CodeSniffer_File $phpcsFile,
        $start,
        $end
    ) {
        $useStatements = array();
        $i           = $start;
        $tokens      = $phpcsFile->getTokens();
        $useTokenPtr = $phpcsFile->findNext(T_USE, $i, $end);

        while ($useTokenPtr !== false) {
            $classNameStart = $phpcsFile->findNext(
                PHP_CodeSniffer_Tokens::$emptyTokens,
                ($useTokenPtr + 1),
                $end,
                true
            );
            $classNameEnd   = $phpcsFile->findNext(
                $this->_classNameTokens,
                ($classNameStart + 1),
                $end,
                true
            );
            $useEnd         = $phpcsFile->findNext(
                array(
                 T_SEMICOLON,
                 T_COMMA,
                ),
                $classNameEnd,
                $end
            );
            $aliasNamePtr   = $phpcsFile->findPrevious(
                PHP_CodeSniffer_Tokens::$emptyTokens,
                ($useEnd - 1),
                null,
                true
            );

            $length    = ($classNameEnd - $classNameStart);
            $className = $phpcsFile->getTokensAsString($classNameStart, $length);

            $className = $this->_getFullyQualifiedClassName($className);
            $useStatements[$className] = $tokens[$aliasNamePtr]['content'];
            $i = ($useEnd + 1);

            if ($tokens[$useEnd]['code'] === T_COMMA) {
                $useTokenPtr = $i;
            } else {
                $useTokenPtr = $phpcsFile->findNext(T_USE, $i, $end);
            }
        }//end while

        return $useStatements;

    }//end getUseStatements()


    /**
     * Get the namespace of the current class file
     *
     * @param PHP_CodeSniffer_File $phpcsFile PHP CS File
     * @param int                  $start     start pointer
     * @param int                  $end       end pointer
     *
     * @return array
     */
    protected  function getNamespace(PHP_CodeSniffer_File $phpcsFile, $start, $end)
    {
        $namespace      = $phpcsFile->findNext(T_NAMESPACE, $start, $end);
        $namespaceStart = $phpcsFile->findNext(
            PHP_CodeSniffer_Tokens::$emptyTokens,
            ($namespace + 1),
            $end,
            true
        );

        if (false === $namespaceStart) {
            return '';
        }

        $namespaceEnd = $phpcsFile->findNext(
            $this->_classNameTokens,
            ($namespaceStart + 1),
            $end,
            true
        );

        $nslen = ($namespaceEnd - $namespaceStart);
        $name  = $phpcsFile->getTokensAsString($namespaceStart, $nslen);

        return "\\{$name}\\";

    }//end getNamespace()


    /**
     * Return the fully qualified class name, e.g. '\Foo\Bar\Faz'
     *
     * @param string $className class name
     *
     * @return string
     */
    private function _getFullyQualifiedClassName($className)
    {
        if ($className[0] !== '\\') {
            $className = "\\{$className}";

            return $className;
        }

        return $className;

    }//end _getFullyQualifiedClassName()


    /**
     * Check if short hand is possible.
     *
     * @param PHP_CodeSniffer_File $phpcsFile     PHP CS File
     * @param array                $useStatements array with class use statements
     * @param string               $className     class name
     * @param string               $nameSpace     name space
     * @param int                  $startPtr      start token pointer
     * @param int                  $endPtr        end token pointer
     * @param bool                 $isDocBlock    true if fixing doc block
     *
     * @return void
     */
    private function _checkShorthandPossible(
        PHP_CodeSniffer_File $phpcsFile,
        $useStatements,
        $className,
        $nameSpace,
        $startPtr,
        $endPtr,
        $isDocBlock = false
    ) {
        $msg     = 'Shorthand possible. Replace "%s" with "%s"';
        $fixable = false;
        $replaceClassName = false;
        $replacement      = null;

        $fullClassName = $this->_getFullyQualifiedClassName($className);

        if ((array_key_exists($fullClassName, $useStatements)) === true) {
            $replacement = $useStatements[$fullClassName];

            $data    = array(
                        $className,
                        $replacement,
                       );
            $fixable = $phpcsFile->addFixableWarning(
                $msg,
                $startPtr,
                'UnnecessaryNameSpaceUsage',
                $data
            );

            $replaceClassName = true;
        } else if (strpos($fullClassName, $nameSpace) === 0) {
            $replacement = substr($fullClassName, strlen($nameSpace));

            $data    = array(
                        $className,
                        $replacement,
                       );
            $fixable = $phpcsFile->addFixableWarning(
                $msg,
                $startPtr,
                'UnnecessaryNameSpaceUsage',
                $data
            );
        }//end if

        if (true === $fixable) {
            $phpcsFile->fixer->beginChangeset();
            if (true === $isDocBlock) {
                $tokens     = $phpcsFile->getTokens();
                $oldContent = $tokens[$startPtr]['content'];
                $newContent = str_replace($className, $replacement, $oldContent);
                $phpcsFile->fixer->replaceToken($startPtr, $newContent);
            } else {
                for ($i = $startPtr; $i < $endPtr; $i++) {
                    $phpcsFile->fixer->replaceToken($i, '');
                }

                if (true === $replaceClassName) {
                    $phpcsFile->fixer->replaceToken($endPtr, $replacement);
                }
            }

            $phpcsFile->fixer->endChangeset();
        }//end if

    }//end _checkShorthandPossible()


}//end class
