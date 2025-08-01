# CraftCMS News Articles Structure Fix - Instructions

## Overview
This guide will help you fix the news articles structure and ordering in your CraftCMS site using direct SQL queries.

**Issues being fixed:**
- Move 200 news articles from level 1 to level 2 under the News parent page
- Reverse the sort order so newest articles appear first

## Prerequisites
- DDEV environment running
- Database access via DDEV
- Basic understanding of SQL transactions

## Step-by-Step Instructions

### 1. Create Database Backup
**CRITICAL: Always backup before making database changes!**

```bash
# Navigate to your project directory
cd /Users/martin/Hypha-Media-Sites/Leading-Edge-2025

# Create a backup
ddev export-db --file=backup-before-news-fix.sql
```

### 2. Access the Database
```bash
# Connect to MySQL via DDEV
ddev mysql
```

### 3. Execute the SQL Script

#### Option A: Run the entire script at once
```sql
-- Copy and paste the entire contents of fix-news-structure.sql
-- The script includes verification steps and uses transactions for safety
```

#### Option B: Run step by step (Recommended for first-time execution)

1. **First, run the identification and verification queries:**
```sql
-- Set variables
SET @news_section_id = (SELECT id FROM sections WHERE handle = 'newsSection');
SET @structure_id = (SELECT structureId FROM sections WHERE handle = 'newsSection');
SET @news_parent_id = (
    SELECT e.id
    FROM elements e
    JOIN entries ent ON e.id = ent.id
    JOIN entrytypes et ON ent.typeId = et.id
    JOIN structures_elements se ON e.id = se.elementId
    WHERE et.handle = 'newsPage'
    AND e.sectionId = @news_section_id
    AND se.level = 1
    AND se.structureId = @structure_id
    LIMIT 1
);

-- Verify components found
SELECT 'News Section ID' as component, @news_section_id as value
UNION ALL SELECT 'Structure ID' as component, @structure_id as value
UNION ALL SELECT 'News Parent ID' as component, @news_parent_id as value;
```

2. **Check current state:**
```sql
-- Count articles at level 1
SELECT 'Current Level 1 News Articles' as info, COUNT(*) as count
FROM elements e
JOIN entries ent ON e.id = ent.id
JOIN entrytypes et ON ent.typeId = et.id
JOIN structures_elements se ON e.id = se.elementId
WHERE et.handle = 'newsArticle'
AND e.sectionId = @news_section_id
AND se.level = 1
AND se.structureId = @structure_id;
```

3. **Execute the fix (if verification looks good):**
```sql
-- Start transaction
START TRANSACTION;

-- Move articles to level 2
UPDATE structures_elements se
JOIN elements e ON se.elementId = e.id
JOIN entries ent ON e.id = ent.id
JOIN entrytypes et ON ent.typeId = et.id
SET se.parentId = @news_parent_id, se.level = 2
WHERE et.handle = 'newsArticle'
AND e.sectionId = @news_section_id
AND se.level = 1
AND se.structureId = @structure_id;

-- Fix sort order (newest first)
CREATE TEMPORARY TABLE temp_sort_order AS
SELECT se.elementId, ROW_NUMBER() OVER (ORDER BY e.dateCreated DESC) as new_sort_order
FROM structures_elements se
JOIN elements e ON se.elementId = e.id
JOIN entries ent ON e.id = ent.id
JOIN entrytypes et ON ent.typeId = et.id
WHERE et.handle = 'newsArticle'
AND e.sectionId = @news_section_id
AND se.level = 2
AND se.parentId = @news_parent_id
AND se.structureId = @structure_id;

UPDATE structures_elements se
JOIN temp_sort_order tso ON se.elementId = tso.elementId
SET se.sortOrder = tso.new_sort_order
WHERE se.structureId = @structure_id
AND se.level = 2
AND se.parentId = @news_parent_id;

DROP TEMPORARY TABLE temp_sort_order;
```

4. **Verify the changes:**
```sql
-- Check updated count
SELECT 'Updated Articles Count' as verification, COUNT(*) as count
FROM structures_elements se
JOIN elements e ON se.elementId = e.id
JOIN entries ent ON e.id = ent.id
JOIN entrytypes et ON ent.typeId = et.id
WHERE et.handle = 'newsArticle'
AND e.sectionId = @news_section_id
AND se.level = 2
AND se.parentId = @news_parent_id
AND se.structureId = @structure_id;

-- Show first 5 articles (should be newest first)
SELECT e.title, e.dateCreated, se.sortOrder, se.level
FROM structures_elements se
JOIN elements e ON se.elementId = e.id
JOIN entries ent ON e.id = ent.id
JOIN entrytypes et ON ent.typeId = et.id
WHERE et.handle = 'newsArticle'
AND e.sectionId = @news_section_id
AND se.level = 2
AND se.parentId = @news_parent_id
AND se.structureId = @structure_id
ORDER BY se.sortOrder
LIMIT 5;
```

5. **Commit or rollback:**
```sql
-- If everything looks correct:
COMMIT;

-- If there are issues:
-- ROLLBACK;
```

### 4. Final Verification
After committing, run this query to confirm the final structure:

```sql
SELECT 'Final Structure Check' as status, se.level, COUNT(*) as article_count
FROM structures_elements se
JOIN elements e ON se.elementId = e.id
JOIN entries ent ON e.id = ent.id
JOIN entrytypes et ON ent.typeId = et.id
WHERE et.handle = 'newsArticle'
AND e.sectionId = (SELECT id FROM sections WHERE handle = 'newsSection')
AND se.structureId = (SELECT structureId FROM sections WHERE handle = 'newsSection')
GROUP BY se.level
ORDER BY se.level;
```

**Expected result:**
- Level 1: 0 articles (or just the News parent page)
- Level 2: 200 articles

### 5. Clear Craft Caches
After the database changes, clear Craft's caches:

```bash
# Exit MySQL
exit

# Clear Craft caches
ddev craft clear-caches/all
```

### 6. Verify in Craft Admin
1. Log into your Craft admin panel
2. Go to Entries → News
3. Verify that:
   - All news articles are now under the News parent (level 2)
   - Articles are ordered with newest first

## Troubleshooting

### If something goes wrong:
1. **During transaction:** Use `ROLLBACK;` to undo changes
2. **After commit:** Restore from backup:
   ```bash
   ddev import-db --src=backup-before-news-fix.sql
   ```

### Common issues:
- **No parent found:** Check that you have a "News" entry with newsPage entry type
- **Wrong section:** Verify the newsSection handle matches your configuration
- **Permission errors:** Ensure DDEV has proper database permissions

## Files Created
- `fix-news-structure.sql` - Complete SQL script
- `backup-before-news-fix.sql` - Database backup (created by you)
- `NEWS_FIX_INSTRUCTIONS.md` - This instruction file

## Support
If you encounter issues, check:
1. CraftCMS logs: `storage/logs/`
2. Database error logs in DDEV
3. Verify your section and entry type handles match the script
