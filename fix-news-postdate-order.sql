-- CraftCMS News Articles Post Date Ordering Fix
-- This script reorders the level 2 news articles by postDate (not dateCreated)
-- Newest postDate articles will appear first (lowest lft values)
-- Oldest postDate articles will appear last (highest lft values)

-- Disable autocommit for transaction safety
SET autocommit = 0;

-- Get the parent entry's left value
SET @parent_lft = (
    SELECT se.lft 
    FROM structureelements se
    JOIN elements e ON se.elementId = e.id
    JOIN entries ent ON e.id = ent.id
    JOIN entrytypes et ON ent.typeId = et.id
    WHERE et.handle = 'newsPage'
    AND ent.sectionId = 7
    AND se.level = 1
);

SELECT CONCAT('Parent left value: ', @parent_lft) as info;

-- Count articles to reorder
SELECT 
    'Articles to reorder by postDate:' as info,
    COUNT(*) as count
FROM structureelements se
JOIN elements e ON se.elementId = e.id
JOIN entries ent ON e.id = ent.id
JOIN entrytypes et ON ent.typeId = et.id
WHERE et.handle = 'newsArticle'
AND ent.sectionId = 7
AND se.level = 2;

-- Show current vs desired order (first 5)
SELECT 'Current order (by lft):' as comparison;
SELECT 
    se.elementId,
    se.lft,
    ent.postDate
FROM structureelements se
JOIN elements e ON se.elementId = e.id
JOIN entries ent ON e.id = ent.id
JOIN entrytypes et ON ent.typeId = et.id
WHERE et.handle = 'newsArticle'
AND ent.sectionId = 7
AND se.level = 2
ORDER BY se.lft
LIMIT 5;

SELECT 'Desired order (by postDate DESC):' as comparison;
SELECT 
    se.elementId,
    se.lft,
    ent.postDate
FROM structureelements se
JOIN elements e ON se.elementId = e.id
JOIN entries ent ON e.id = ent.id
JOIN entrytypes et ON ent.typeId = et.id
WHERE et.handle = 'newsArticle'
AND ent.sectionId = 7
AND se.level = 2
ORDER BY ent.postDate DESC
LIMIT 5;

-- Start transaction
START TRANSACTION;

-- Reorder all level 2 news articles by postDate (newest first)
-- Each article gets lft = parent_lft + (row_number * 2) - 1
-- Each article gets rgt = parent_lft + (row_number * 2)

UPDATE structureelements se
JOIN elements e ON se.elementId = e.id
JOIN entries ent ON e.id = ent.id
JOIN entrytypes et ON ent.typeId = et.id
JOIN (
    SELECT 
        se2.elementId,
        ROW_NUMBER() OVER (ORDER BY ent2.postDate DESC) as row_num
    FROM structureelements se2
    JOIN elements e2 ON se2.elementId = e2.id
    JOIN entries ent2 ON e2.id = ent2.id
    JOIN entrytypes et2 ON ent2.typeId = et2.id
    WHERE et2.handle = 'newsArticle'
    AND ent2.sectionId = 7
    AND se2.level = 2
) ordered ON se.elementId = ordered.elementId
SET 
    se.lft = @parent_lft + (ordered.row_num * 2) - 1,
    se.rgt = @parent_lft + (ordered.row_num * 2)
WHERE et.handle = 'newsArticle'
AND ent.sectionId = 7
AND se.level = 2;

-- Update the parent's right value to encompass all children
SET @total_articles = (
    SELECT COUNT(*)
    FROM structureelements se
    JOIN elements e ON se.elementId = e.id
    JOIN entries ent ON e.id = ent.id
    JOIN entrytypes et ON ent.typeId = et.id
    WHERE et.handle = 'newsArticle'
    AND ent.sectionId = 7
    AND se.level = 2
);

UPDATE structureelements se
JOIN elements e ON se.elementId = e.id
JOIN entries ent ON e.id = ent.id
JOIN entrytypes et ON ent.typeId = et.id
SET se.rgt = @parent_lft + (@total_articles * 2) + 1
WHERE et.handle = 'newsPage'
AND ent.sectionId = 7
AND se.level = 1;

-- Commit the transaction
COMMIT;

-- Re-enable autocommit
SET autocommit = 1;

-- Verify the postDate ordering
SELECT 'Verification - First 10 articles (should be newest postDate first):' as status;
SELECT 
    se.elementId,
    se.level,
    se.lft,
    se.rgt,
    ent.postDate
FROM structureelements se
JOIN elements e ON se.elementId = e.id
JOIN entries ent ON e.id = ent.id
JOIN entrytypes et ON ent.typeId = et.id
WHERE et.handle = 'newsArticle'
AND ent.sectionId = 7
AND se.level = 2
ORDER BY se.lft
LIMIT 10;

SELECT 'SUCCESS: News articles reordered by postDate (newest first)!' as result;
