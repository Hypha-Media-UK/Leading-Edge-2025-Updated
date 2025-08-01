-- CraftCMS News Articles Structure Fix
-- This script will:
-- 1. Move news articles from level 1 to level 2 under the News parent
-- 2. Reverse the sort order so newest articles appear first
-- 
-- IMPORTANT: Create a database backup before running this script!
-- Run: ddev export-db --file=backup-before-news-fix.sql

-- Step 1: Identify the News section and structure
SET @news_section_id = (
    SELECT id 
    FROM sections 
    WHERE handle = 'newsSection'
);

SET @structure_id = (
    SELECT structureId 
    FROM sections 
    WHERE handle = 'newsSection'
);

-- Step 2: Find the parent News entry (should be the newsPage entry type at level 1)
SET @news_parent_id = (
    SELECT e.id
    FROM elements e
    JOIN entries ent ON e.id = ent.id
    JOIN entrytypes et ON ent.typeId = et.id
    JOIN structureelements se ON e.id = se.elementId
    WHERE et.handle = 'newsPage'
    AND ent.sectionId = @news_section_id
    AND se.level = 1
    AND se.structureId = @structure_id
    LIMIT 1
);

-- Step 3: Verify we found the components (for debugging)
SELECT 
    'News Section ID' as component, 
    @news_section_id as value
UNION ALL
SELECT 
    'Structure ID' as component, 
    @structure_id as value
UNION ALL
SELECT 
    'News Parent ID' as component, 
    @news_parent_id as value;

-- Step 4: Show current news articles that need to be moved
SELECT 
    'Current Level 1 News Articles' as info,
    COUNT(*) as count
FROM elements e
JOIN entries ent ON e.id = ent.id
JOIN entrytypes et ON ent.typeId = et.id
JOIN structureelements se ON e.id = se.elementId
WHERE et.handle = 'newsArticle'
AND ent.sectionId = @news_section_id
AND se.level = 1
AND se.structureId = @structure_id;

-- Step 5: Begin transaction for safety
START TRANSACTION;

-- Step 6: Update the hierarchy - move news articles to level 2 under News parent
UPDATE structureelements se
JOIN elements e ON se.elementId = e.id
JOIN entries ent ON e.id = ent.id
JOIN entrytypes et ON ent.typeId = et.id
SET 
    se.parentId = @news_parent_id,
    se.level = 2
WHERE et.handle = 'newsArticle'
AND ent.sectionId = @news_section_id
AND se.level = 1
AND se.structureId = @structure_id;

-- Step 7: Update sort order - newest articles first
-- Create a temporary table with new sort orders based on dateCreated (descending)
CREATE TEMPORARY TABLE temp_sort_order AS
SELECT 
    se.elementId,
    ROW_NUMBER() OVER (ORDER BY e.dateCreated DESC) as new_sort_order
FROM structureelements se
JOIN elements e ON se.elementId = e.id
JOIN entries ent ON e.id = ent.id
JOIN entrytypes et ON ent.typeId = et.id
WHERE et.handle = 'newsArticle'
AND ent.sectionId = @news_section_id
AND se.level = 2
AND se.parentId = @news_parent_id
AND se.structureId = @structure_id;

-- Apply the new sort order
UPDATE structureelements se
JOIN temp_sort_order tso ON se.elementId = tso.elementId
SET se.sortOrder = tso.new_sort_order
WHERE se.structureId = @structure_id
AND se.level = 2
AND se.parentId = @news_parent_id;

-- Step 8: Verify the changes
SELECT 
    'Updated Articles Count' as verification,
    COUNT(*) as count
FROM structureelements se
JOIN elements e ON se.elementId = e.id
JOIN entries ent ON e.id = ent.id
JOIN entrytypes et ON ent.typeId = et.id
WHERE et.handle = 'newsArticle'
AND ent.sectionId = @news_section_id
AND se.level = 2
AND se.parentId = @news_parent_id
AND se.structureId = @structure_id;

-- Show first 5 articles to verify order (newest first)
SELECT 
    e.title,
    e.dateCreated,
    se.sortOrder,
    se.level
FROM structureelements se
JOIN elements e ON se.elementId = e.id
JOIN entries ent ON e.id = ent.id
JOIN entrytypes et ON ent.typeId = et.id
WHERE et.handle = 'newsArticle'
AND ent.sectionId = @news_section_id
AND se.level = 2
AND se.parentId = @news_parent_id
AND se.structureId = @structure_id
ORDER BY se.sortOrder
LIMIT 5;

-- Step 9: If everything looks correct, commit the transaction
-- COMMIT;

-- Step 10: If there are issues, rollback the transaction
-- ROLLBACK;

-- Clean up temporary table
DROP TEMPORARY TABLE temp_sort_order;

-- Final verification query - run this after committing
-- SELECT 
--     'Final Structure Check' as status,
--     se.level,
--     COUNT(*) as article_count
-- FROM structureelements se
-- JOIN elements e ON se.elementId = e.id
-- JOIN entries ent ON e.id = ent.id
-- JOIN entrytypes et ON ent.typeId = et.id
-- WHERE et.handle = 'newsArticle'
-- AND ent.sectionId = @news_section_id
-- AND se.structureId = @structure_id
-- GROUP BY se.level
-- ORDER BY se.level;
