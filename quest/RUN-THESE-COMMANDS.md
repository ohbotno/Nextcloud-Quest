# Commands to Fix the Age System

Run these commands **on your Docker server** where Nextcloud is installed.

## Step 1: Run the Migration

```bash
# Enter your Nextcloud Docker container
docker exec -it -u www-data nextcloud_container_name php occ migrations:execute quest Version1014Date20250930130000
```

**Expected output:**
```
Migration executed: OCA\NextcloudQuest\Migration\Version1014Date20250930130000
Created character age: Stone Age
Created character age: Bronze Age
Created character age: Iron Age
...
Character ages initialized successfully
```

## Step 2: Verify Ages in Database

```bash
# Check what ages are now in the database
docker exec -it -u www-data nextcloud_container_name php occ db:execute-query \
  "SELECT age_key, age_name, min_level, max_level FROM oc_ncquest_character_ages ORDER BY min_level"
```

**Expected output:**
```
stone     | Stone Age       | 1  | 9
bronze    | Bronze Age      | 10 | 19
iron      | Iron Age        | 20 | 29
medieval  | Medieval Age    | 30 | 39
renaissance | Renaissance   | 40 | 49
industrial | Industrial Age | 50 | 59
modern    | Modern Age      | 60 | 74
digital   | Digital Age     | 75 | 99
space     | Space Age       | 100| NULL
```

## Step 3: Force Age Recalculation

Now you need to trigger the age recalculation for your character. You have two options:

### Option A: Via Browser

1. Open your browser's Developer Tools (F12)
2. Go to the Console tab
3. Paste and run:

```javascript
fetch('/apps/quest/api/character/recalculate-age', {
    method: 'POST',
    headers: {
        'requesttoken': OC.requestToken
    }
}).then(r => r.json()).then(data => console.log(data));
```

### Option B: Via curl (from your server)

```bash
# Replace YOUR_USERNAME and YOUR_PASSWORD with your Nextcloud credentials
curl -X POST \
  -u YOUR_USERNAME:YOUR_PASSWORD \
  https://your-nextcloud-domain/apps/quest/api/character/recalculate-age
```

**Expected output:**
```json
{
  "status": "success",
  "message": "Age recalculated successfully",
  "data": {
    "current_level": 11,
    "lifetime_xp": 1234,
    "new_age": {
      "age_key": "bronze",
      "age_name": "Bronze Age",
      "min_level": 10,
      "max_level": 19
    },
    "character_data": {
      "current_age": {
        "key": "bronze",
        "name": "Bronze Age"
      }
    }
  }
}
```

## Step 4: Verify on Character Page

1. Go to: `https://your-nextcloud/apps/quest/character`
2. You should now see:
   - **Current Age: Bronze Age** ⚒️
   - **Next Age: Iron Age (Level 20)**

## Troubleshooting

### If you don't know your Docker container name:

```bash
# List all Docker containers
docker ps

# Look for the Nextcloud container, then use its name in the commands above
```

### If migration says "already executed":

That's fine! The migration was already run. Skip to Step 3 to force recalculation.

### If you get "table already exists" error:

That's also fine - it means the table structure is correct. The migration will still update the age data.

### If character page still shows Stone Age:

1. Check browser console for errors (F12 → Console tab)
2. Clear browser cache and hard refresh (Ctrl+Shift+R)
3. Run the debug endpoint to see what the system thinks your age is:

```bash
curl -u YOUR_USERNAME:YOUR_PASSWORD \
  https://your-nextcloud-domain/apps/quest/api/character/debug-age
```

## Quick All-in-One Script

Save this as `fix-age.sh` on your Docker host:

```bash
#!/bin/bash
CONTAINER_NAME="nextcloud"  # Change this to your container name

echo "🔧 Fixing Age System..."
echo ""

echo "1️⃣ Running migration..."
docker exec -it -u www-data $CONTAINER_NAME \
  php occ migrations:execute quest Version1014Date20250930130000

echo ""
echo "2️⃣ Checking ages in database..."
docker exec -it -u www-data $CONTAINER_NAME \
  php occ db:execute-query \
  "SELECT age_key, age_name, min_level, max_level FROM oc_ncquest_character_ages ORDER BY min_level"

echo ""
echo "✅ Migration complete!"
echo ""
echo "📋 Next step: Force age recalculation"
echo "Run this in your browser console:"
echo ""
echo "fetch('/apps/quest/api/character/recalculate-age', {"
echo "    method: 'POST',"
echo "    headers: { 'requesttoken': OC.requestToken }"
echo "}).then(r => r.json()).then(console.log);"
```

Then run it:
```bash
chmod +x fix-age.sh
./fix-age.sh
```

---

**After running these commands, your level 11 character should show as Bronze Age! 🎉**
