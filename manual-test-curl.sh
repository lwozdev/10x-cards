#!/bin/bash

# Manual Test: POST /api/sets - Create set with AI cards
# This script tests the endpoint using curl

echo "========================================="
echo "Testing POST /api/sets endpoint"
echo "========================================="
echo ""

# Test data
REQUEST_DATA='{
  "name": "Biologia - Test Manualny cURL",
  "cards": [
    {
      "front": "Co to jest fotosynteza?",
      "back": "Proces wytwarzania glukozy przez rośliny z CO2 i wody przy użyciu energii słonecznej",
      "origin": "ai",
      "edited": false
    },
    {
      "front": "Jakie są produkty fotosyntezy?",
      "back": "Glukoza (C6H12O6) i tlen (O2)",
      "origin": "ai",
      "edited": true
    },
    {
      "front": "Gdzie zachodzi fotosynteza?",
      "back": "W chloroplastach komórek roślinnych",
      "origin": "manual",
      "edited": false
    }
  ]
}'

echo "Sending POST request to http://localhost:8099/api/sets"
echo ""

# Make the request and capture response with headers
RESPONSE=$(curl -i -X POST http://localhost:8099/api/sets \
  -u "test@example.com:test123" \
  -H "Content-Type: application/json" \
  -d "$REQUEST_DATA" \
  -w "\n\nHTTP_CODE: %{http_code}\n" \
  2>/dev/null)

echo "$RESPONSE"
echo ""
echo "========================================="
echo "Expected: HTTP 201 Created"
echo "Expected JSON: {\"id\": \"uuid\", \"name\": \"Biologia - Test Manualny cURL\", \"card_count\": 3}"
echo "Expected Header: Location: /api/sets/{uuid}"
echo "========================================="
echo ""
echo "Database verification queries:"
echo "1. Check sets table:"
echo "   SELECT * FROM sets WHERE name = 'Biologia - Test Manualny cURL';"
echo ""
echo "2. Check cards table (should have 3 cards):"
echo "   SELECT id, front, origin, edited_by_user_at FROM cards WHERE set_id = (SELECT id FROM sets WHERE name = 'Biologia - Test Manualny cURL');"
echo ""
echo "3. Check analytics_events table:"
echo "   SELECT event_type, payload FROM analytics_events WHERE event_type = 'set_created' ORDER BY occurred_at DESC LIMIT 1;"
echo ""
