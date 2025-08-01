-- CraftCMS v5.8 News Articles Structure Fix - Direct SQL Approach
-- This script directly manipulates the nested set structure
-- IMPORTANT: Database backup already created: backup-before-news-fix.sql

-- First, let's see what we're working with
SELECT 'Current structure before changes:' as info;
SELECT 
    se.elementId,
    se.level,
    se.lft,
    se.rgt,
    CASE 
        WHEN et.handle = 'newsPage' THEN 'NEWS PAGE'
        WHEN et.handle = 'newsArticle' THEN 'NEWS ARTICLE'
        ELSE et.handle
    END as type,
    e.dateCreated
FROM structureelements se
JOIN elements e ON se.elementId = e.id
JOIN entries ent ON e.id = ent.id
JOIN entrytypes et ON ent.typeId = et.id
WHERE ent.sectionId = 7
ORDER BY se.lft
LIMIT 10;

-- Get the parent entry's right value (we need to insert children before this)
SET @parent_rgt = (
    SELECT se.rgt 
    FROM structureelements se
    JOIN elements e ON se.elementId = e.id
    JOIN entries ent ON e.id = ent.id
    JOIN entrytypes et ON ent.typeId = et.id
    WHERE et.handle = 'newsPage'
    AND ent.sectionId = 7
    AND se.level = 1
);

SELECT CONCAT('Parent right value: ', @parent_rgt) as info;

-- Count articles to move
SELECT 
    'Articles to move:' as info,
    COUNT(*) as count
FROM structureelements se
JOIN elements e ON se.elementId = e.id
JOIN entries ent ON e.id = ent.id
JOIN entrytypes et ON ent.typeId = et.id
WHERE et.handle = 'newsArticle'
AND ent.sectionId = 7
AND se.level = 1;

-- Start transaction
START TRANSACTION;

-- Step 1: Make space in the nested set for the articles
-- We need to shift all nodes with lft >= parent_rgt to the right
-- Each article needs 2 positions (lft and rgt), so we need space for 199 * 2 = 398 positions

UPDATE structureelements 
SET lft = lft + 398 
WHERE lft >= @parent_rgt;

UPDATE structureelements 
SET rgt = rgt + 398 
WHERE rgt >= @parent_rgt;

-- Step 2: Move the articles to be children of the parent
-- We'll assign new lft/rgt values and set level = 2
-- Order by dateCreated DESC so newest articles get the lowest lft values (appear first)

SET @current_lft = @parent_rgt;

UPDATE structureelements se
JOIN elements e ON se.elementId = e.id
JOIN entries ent ON e.id = ent.id
JOIN entrytypes et ON ent.typeId = et.id
JOIN (
    SELECT 
        se2.elementId,
        ROW_NUMBER() OVER (ORDER BY e2.dateCreated DESC) as row_num
    FROM structureelements se2
    JOIN elements e2 ON se2.elementId = e2.id
    JOIN entries ent2 ON e2.id = ent2.id
    JOIN entrytypes et2 ON ent2.typeId = et2.id
    WHERE et2.handle = 'newsArticle'
    AND ent2.sectionId = 7
    AND se2.level = 1
) ordered ON se.elementId = ordered.elementId
SET 
    se.level = 2,
    se.lft = @parent_rgt + (ordered.row_num - 1) * 2 + 1,
    se.rgt = @parent_rgt + (ordered.row_num - 1) * 2 + 2
WHERE et.handle = 'newsArticle'
AND ent.sectionId = 7
AND se.level = 1;

-- Step 3: Update the parent's right value to encompass all children
UPDATE structureelements se
JOIN elements e ON se.elementId = e.id
JOIN entries ent ON e.id = ent.id
JOIN entrytypes et ON ent.typeId = et.id
SET se.rgt = @parent_rgt + 398
WHERE et.handle = 'newsPage'
AND ent.sectionId = 7
AND se.level = 1;

-- Verify the changes
SELECT 'Verification after changes:' as info;

SELECT 
    'Level distribution:' as check_type,
    se.level,
    COUNT(*) as count
FROM structureelements se
JOIN elements e ON se.elementId = e.id
JOIN entries ent ON e.id = ent.id
JOIN entrytypes et ON ent.typeId = et.id
WHERE et.handle = 'newsArticle'
AND ent.sectionId = 7
GROUP BY se.level
ORDER BY se.level;

-- Show first 5 articles to verify order (newest first)
SELECT 
    'First 5 articles (newest first):' as check_type,
    se.elementId,
    se.level,
    se.lft,
    e.dateCreated
FROM structureelements se
JOIN elements e ON se.elementId = e.id
JOIN entries ent ON e.id = ent.id
JOIN entrytypes et ON ent.typeId = et.id
WHERE et.handle = 'newsArticle'
AND ent.sectionId = 7
AND se.level = 2
ORDER BY se.lft
LIMIT 5;

-- If everything looks good, commit the transaction
-- COMMIT;

-- If there are issues, rollback
-- ROLLBACK;

SELECT 'Transaction ready - review results above, then run COMMIT; or ROLLBACK;' as final_instruction;
