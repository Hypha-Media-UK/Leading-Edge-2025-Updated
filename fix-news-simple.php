<?php
/**
 * Simple News Structure Fix
 * Run with: ddev craft exec "$(cat fix-news-simple.php)"
 */

use craft\elements\Entry;

// Get the News section
$newsSection = Craft::$app->sections->getSectionByHandle('newsSection');
if (!$newsSection) {
    echo "Error: Could not find newsSection\n";
    return;
}

// Get the structure
$structure = Craft::$app->structures->getStructureById($newsSection->structureId);
if (!$structure) {
    echo "Error: Could not find structure\n";
    return;
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
    return;
}

echo "Found parent News entry: {$parentEntry->title} (ID: {$parentEntry->id})\n";

// Find all news articles at level 1 (these need to be moved)
$articlesToMove = Entry::find()
    ->section('newsSection')
    ->type('newsArticle')
    ->level(1)
    ->orderBy('dateCreated ASC')
    ->all();

echo "Found " . count($articlesToMove) . " articles to move\n";

if (empty($articlesToMove)) {
    echo "No articles to move.\n";
    return;
}

// Start transaction
$transaction = Craft::$app->db->beginTransaction();

try {
    // Move each article to be a child of the parent entry
    // Process in reverse order so newest articles end up first
    $articlesToMove = array_reverse($articlesToMove);
    
    foreach ($articlesToMove as $index => $article) {
        echo "Moving: {$article->title}\n";
        
        $success = Craft::$app->structures->moveAfter(
            $structure->id,
            $article,
            $parentEntry,
            'child'
        );
        
        if (!$success) {
            throw new Exception("Failed to move: {$article->title}");
        }
    }
    
    $transaction->commit();
    echo "Success! Moved " . count($articlesToMove) . " articles.\n";
    
} catch (Exception $e) {
    $transaction->rollBack();
    echo "Error: " . $e->getMessage() . "\n";
}

// Verify results
$level1 = Entry::find()->section('newsSection')->type('newsArticle')->level(1)->count();
$level2 = Entry::find()->section('newsSection')->type('newsArticle')->level(2)->count();
echo "Final: Level 1: {$level1}, Level 2: {$level2}\n";
