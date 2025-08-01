-- CraftCMS v5.8 News Articles Structure Fix - Auto-commit version
-- This script directly manipulates the nested set structure with immediate commits

-- Disable autocommit temporarily for transaction safety
SET autocommit = 0;

-- Get the parent entry's right value
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

-- Start explicit transaction
START TRANSACTION;

-- Step 1: Make space in the nested set for the articles
UPDATE structureelements 
SET lft = lft + 398 
WHERE lft >= @parent_rgt;

UPDATE structureelements 
SET rgt = rgt + 398 
WHERE rgt >= @parent_rgt;

-- Step 2: Move the articles to be children of the parent
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

-- Step 3: Update the parent's right value
UPDATE structureelements se
JOIN elements e ON se.elementId = e.id
JOIN entries ent ON e.id = ent.id
JOIN entrytypes et ON ent.typeId = et.id
SET se.rgt = @parent_rgt + 398
WHERE et.handle = 'newsPage'
AND ent.sectionId = 7
AND se.level = 1;

-- Commit the transaction
COMMIT;

-- Re-enable autocommit
SET autocommit = 1;

-- Verify the changes
SELECT 'Final verification:' as status;
SELECT 
    se.level,
    COUNT(*) as article_count
FROM structureelements se
JOIN elements e ON se.elementId = e.id
JOIN entries ent ON e.id = ent.id
JOIN entrytypes et ON ent.typeId = et.id
WHERE et.handle = 'newsArticle'
AND ent.sectionId = 7
GROUP BY se.level
ORDER BY se.level;

SELECT 'SUCCESS: All news articles moved to level 2 and ordered by newest first!' as result;
