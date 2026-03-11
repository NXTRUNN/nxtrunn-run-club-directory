#!/usr/bin/env python3
"""
Scrape ALL clubs from daily.nyc API and save to JSON + Excel.
API: https://api.daily.nyc/clubs/list (cursor-based pagination, 30 per page)
"""

import requests
import json
import time
import os
from openpyxl import Workbook

BASE_URL = "https://api.daily.nyc/clubs/list"
OUTPUT_DIR = os.path.dirname(os.path.abspath(__file__))
JSON_FILE = os.path.join(OUTPUT_DIR, "daily-nyc-all-clubs.json")
XLSX_FILE = os.path.join(OUTPUT_DIR, "daily-nyc-all-clubs.xlsx")

# Known tag names to create boolean columns for
TAG_COLUMNS = [
    "Women", "Black", "LGBTQ+", "Asian", "Latinx",
    "Beginner Friendly", "Competitive", "Coached", "Charity", "Trail",
    "Road", "Track", "Social", "Walking", "Triathlon",
]


def fetch_all_clubs():
    """Paginate through the API and collect all clubs."""
    all_clubs = []
    cursor = None
    page = 0

    while True:
        page += 1
        url = f"{BASE_URL}?pagination_limit=30&query="
        if cursor:
            url += f"&pagination_cursor={cursor}"

        try:
            r = requests.get(url, timeout=30)
            r.raise_for_status()
            data = r.json()
        except Exception as e:
            print(f"  Error on page {page}: {e}. Retrying in 3s...")
            time.sleep(3)
            continue

        entries = data.get("entries", [])
        all_clubs.extend(entries)

        if page % 20 == 0 or not data.get("has_more"):
            print(f"  Page {page}: {len(all_clubs)} clubs total")

        if not data.get("has_more"):
            break

        cursor = data.get("next_cursor")
        # Small delay to be respectful
        time.sleep(0.3)

    return all_clubs


def save_json(clubs):
    """Save raw JSON."""
    with open(JSON_FILE, "w", encoding="utf-8") as f:
        json.dump(clubs, f, indent=2, ensure_ascii=False)
    print(f"Saved {len(clubs)} clubs to {JSON_FILE}")


def save_excel(clubs):
    """Save to Excel with all fields + tag boolean columns."""
    wb = Workbook()
    ws = wb.active
    ws.title = "All Clubs"

    # Collect all unique tag names from the data
    all_tag_names = set()
    for club in clubs:
        for tag in club.get("tags", []):
            all_tag_names.add(tag.get("name", ""))
    all_tag_names = sorted(all_tag_names)

    # Headers
    base_headers = [
        "api_id", "id", "name", "description", "slug", "website",
        "instagram_handle", "tiktok_handle", "strava_club_id",
        "avatar_url", "is_verified", "neighborhood_label",
        "all_neighborhoods", "all_tags",
    ]
    tag_headers = [f"tag_{t.lower().replace(' ', '_').replace('+', '')}" for t in all_tag_names]
    headers = base_headers + tag_headers
    ws.append(headers)

    # Data rows
    for club in clubs:
        tags = club.get("tags", [])
        tag_names = [t.get("name", "") for t in tags]
        neighborhoods = club.get("neighborhoods", [])
        neighborhood_names = [n.get("name", "") for n in neighborhoods]

        row = [
            club.get("api_id", ""),
            club.get("id", ""),
            club.get("name", ""),
            club.get("description", ""),
            club.get("slug", ""),
            club.get("website", ""),
            club.get("instagram_handle", ""),
            club.get("tiktok_handle", ""),
            club.get("strava_club_id", ""),
            club.get("avatar_url", ""),
            club.get("is_verified", False),
            club.get("neighborhood_label", ""),
            ", ".join(neighborhood_names),
            ", ".join(tag_names),
        ]

        # Tag boolean columns
        for tag_name in all_tag_names:
            row.append(1 if tag_name in tag_names else 0)

        ws.append(row)

    # Auto-width for first few columns
    for col_idx, header in enumerate(headers[:12], 1):
        ws.column_dimensions[ws.cell(1, col_idx).column_letter].width = max(len(header) + 2, 15)

    wb.save(XLSX_FILE)
    print(f"Saved {len(clubs)} clubs to {XLSX_FILE}")
    print(f"  {len(base_headers)} base columns + {len(all_tag_names)} tag columns = {len(headers)} total columns")


if __name__ == "__main__":
    print("Scraping all clubs from daily.nyc...")
    print(f"Output: {OUTPUT_DIR}\n")

    clubs = fetch_all_clubs()
    print(f"\nTotal clubs scraped: {len(clubs)}")

    save_json(clubs)
    save_excel(clubs)
    print("\nDone!")
