<?php

namespace craft\contentmigrations;

use craft\db\Migration;
use craft\elements\Entry;

/**
 * m250108_112949_fix_news_structure migration.
 */
class m250108_112949_fix_news_structure extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        echo "CraftCMS News Articles Structure Fix (Craft v5.8 compatible)\n";
        echo "===========================================================\n\n";

        // Get the News section
        $newsSection = \Craft::$app->sections->getSectionByHandle('newsSection');
        if (!$newsSection) {
            echo "Error: Could not find newsSection\n";
            return false;
        }

        // Get the structure
        $structure = \Craft::$app->structures->getStructureById($newsSection->structureId);
        if (!$structure) {
            echo "Error: Could not find structure\n";
            return false;
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
            return false;
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
            echo "No articles to move. Migration complete.\n";
            return true;
        }

        try {
            // Move each article to be a child of the parent entry
            // Process in reverse order so newest articles end up first
            $articlesToMove = array_reverse($articlesToMove);
            
            foreach ($articlesToMove as $index => $article) {
                echo "Moving article: {$article->title} (ID: {$article->id})\n";
                
                // In Craft v5.8, use the moveAfter method with proper parameters
                $success = \Craft::$app->structures->moveAfter(
                    $structure->id,
                    $article,
                    $parentEntry,
                    'child'
                );
                
                if (!$success) {
                    throw new \Exception("Failed to move article: {$article->title}");
                }
            }
            
            echo "\nSuccess! Moved " . count($articlesToMove) . " articles to level 2 under the News parent.\n";
            echo "Articles are now ordered with newest first.\n";
            
        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
            return false;
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

        echo "\nMigration complete! Please check your Craft admin panel to verify the changes.\n";
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "This migration cannot be reverted. Please restore from backup if needed.\n";
        return false;
    }
}
