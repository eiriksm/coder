<?php
/**
 * Largely copied from
 * PHP_CodeSniffer\Standards\Squiz\Sniffs\Classes\ClassFileNameSniff.
 *
 * Extended to support anonymous classes and Drupal core version.
 */

namespace Drupal\Sniffs\Classes;

use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use DrupalPractice\Project;

class ClassFileNameSniff implements Sniff
{


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return [
            T_CLASS,
            T_INTERFACE,
            T_TRAIT,
        ];

    }//end register()


    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the current token in
     *                                               the stack passed in $tokens.
     *
     * @return int
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        // This check only applies to Drupal 8+, in Drupal 7 we can have classes
        // in all kinds of files.
        if (Project::getCoreVersion($phpcsFile) < 8) {
            return ($phpcsFile->numTokens + 1);
        }

        $fullPath = basename($phpcsFile->getFilename());
        $fileName = substr($fullPath, 0, strrpos($fullPath, '.'));
        if ($fileName === '') {
            // No filename probably means STDIN, so we can't do this check.
            return ($phpcsFile->numTokens + 1);
        }

        // If the file is not a known php file (like a txt file or a md file),
        // we do not need to check if the file is named after the class we
        // found.
        $extension     = pathinfo($fullPath, PATHINFO_EXTENSION);
        $phpExtensions = [
            'inc',
            'php',
            'module',
            'theme',
        ];
        if (in_array($extension, $phpExtensions) === false) {
            return ($phpcsFile->numTokens + 1);
        }

        $tokens  = $phpcsFile->getTokens();
        $decName = $phpcsFile->findNext(Tokens::$emptyTokens, ($stackPtr + 1), null, true);

        // Subcontext classes for Behat Drupal Extension are located in
        // *.behat.inc files and should be named ModuleNameSubContext.
        $isSubContext = preg_match('/\.behat$/', $fileName);
        if ($isSubContext === 1) {
            $fileName = preg_replace('/\.behat$/', '', $fileName);
            $fileName = str_replace('_', '', ucwords($fileName, '_')).'SubContext';
        }

        if ($tokens[$decName]['code'] === T_STRING
            && $tokens[$decName]['content'] !== $fileName
        ) {
            $error = '%s name doesn\'t match filename; expected "%s %s"';
            $data  = [
                ucfirst($tokens[$stackPtr]['content']),
                $tokens[$stackPtr]['content'],
                $fileName,
            ];
            $phpcsFile->addError($error, $stackPtr, 'NoMatch', $data);
        }

        // Only check the first class in a file, we don't care about helper
        // classes in tests for example.
        return ($phpcsFile->numTokens + 1);

    }//end process()


}//end class
