#!/usr/bin/env php
<?php
/**
 * CraftCMS News Articles Structure Fix - Standalone Script
 * This script properly bootstraps Craft and fixes the news structure
 * 
 * Run with: ddev exec php fix-news-articles.php
 */

// Bootstrap Craft
define('CRAFT_BASE_PATH', __DIR__);
require_once CRAFT_BASE_PATH . '/vendor/autoload.php';

// Load the bootstrap file but don't run the app yet
$config = require CRAFT_BASE_PATH . '/config/app.php';
$app = new craft\web\Application($config);

use craft\elements\Entry;

echo "CraftCMS News Articles Structure Fix (Craft v5.8 compatible)\n";
echo "===========================================================\n\n";

try {
    // Get the News section
    $newsSection = Craft::$app->sections->getSectionByHandle('newsSection');
    if (!$newsSection) {
        echo "Error: Could not find newsSection\n";
        exit(1);
    }

    // Get the structure
    $structure = Craft::$app->structures->getStructureById($newsSection->structureId);
    if (!$structure) {
        echo "Error: Could not find structure\n";
        exit(1);
    }

    echo "Found News section (ID: {$newsSection->id}) with structure (ID: {$structure->id})\n";

    // Find the parent News entry (newsPage entry type at level 1)
    $parentEntry = Entry::find()
        ->section('newsSection')
        ->type('newsPage')
        ->level(1)
        ->one();

    if (!$parentEntry) {
        echo "Error: Could not find parent News entry\n";
        exit(1);
    }

    echo "Found parent News entry: {$parentEntry->title} (ID: {$parentEntry->id})\n";

    // Find all news articles at level 1 (these need to be moved)
    $articlesToMove = Entry::find()
        ->section('newsSection')
        ->type('newsArticle')
        ->level(1)
        ->orderBy('dateCreated ASC') // Get oldest first so we can reverse the order
        ->all();

    echo "Found " . count($articlesToMove) . " articles to move\n\n";

    if (empty($articlesToMove)) {
        echo "No articles to move. Task complete.\n";
        exit(0);
    }

    // Start transaction
    $transaction = Craft::$app->db->beginTransaction();

    try {
        // Move each article to be a child of the parent entry
        // Process in reverse order so newest articles end up first
        $articlesToMove = array_reverse($articlesToMove);
        
        foreach ($articlesToMove as $index => $article) {
            echo "Moving article: {$article->title} (ID: {$article->id})\n";
            
            // Use Craft's structure service to move the article
            $success = Craft::$app->structures->moveAfter(
                $structure->id,
                $article,
                $parentEntry,
                'child'
            );
            
            if (!$success) {
                throw new Exception("Failed to move article: {$article->title}");
            }
        }
        
        // Commit the transaction
        $transaction->commit();
        echo "\nSuccess! Moved " . count($articlesToMove) . " articles to level 2 under the News parent.\n";
        echo "Articles are now ordered with newest first.\n";
        
    } catch (Exception $e) {
        // Rollback on error
        $transaction->rollBack();
        echo "Error: " . $e->getMessage() . "\n";
        echo "All changes have been rolled back.\n";
        exit(1);
    }

    // Verify the results
    $level1Articles = Entry::find()
        ->section('newsSection')
        ->type('newsArticle')
        ->level(1)
        ->count();

    $level2Articles = Entry::find()
        ->section('newsSection')
        ->type('newsArticle')
        ->level(2)
        ->count();

    echo "\nFinal verification:\n";
    echo "Level 1 articles: {$level1Articles}\n";
    echo "Level 2 articles: {$level2Articles}\n";

    // Show first 5 articles to verify order
    $firstFive = Entry::find()
        ->section('newsSection')
        ->type('newsArticle')
        ->level(2)
        ->orderBy('lft ASC')
        ->limit(5)
        ->all();

    echo "\nFirst 5 articles (should be newest first):\n";
    foreach ($firstFive as $article) {
        echo "- {$article->title} (Created: {$article->dateCreated->format('Y-m-d H:i:s')})\n";
    }

    echo "\nTask complete! Please check your Craft admin panel to verify the changes.\n";

} catch (Exception $e) {
    echo "Fatal error: " . $e->getMessage() . "\n";
    exit(1);
}
