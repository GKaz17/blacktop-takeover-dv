# Design and Project Proposal

## Project interpretation

Blacktop Takeover is a digital operating layer for Gauteng street basketball. It is designed around the idea that a community competition is more than one tournament. Players enter through local city routes, build recognised squads, and climb a visible competitive ladder toward the provincial D.O.G. game.

The real-world problem is fragmented tournament administration. Registration lists, captain communication, approvals, fixtures, scores, and standings are often managed in separate chats or documents. The application brings those activities into one relational system while keeping the public experience energetic and culturally specific.

## Competition ladder

1. Johannesburg squads begin at COJ and compete for the road to KOS.
2. Pretoria squads begin at COP and compete for the road to KON.
3. KON and KOS establish the strongest northern and southern teams.
4. The leading teams advance to D.O.G., the final provincial streetball game.

## Intended users

- Visitors discover the movement, tournaments, fixtures, and results without creating an account.
- Players join squads through invitation codes and manage their own playing details.
- Captains create squads, manage active rosters, select vice-captains, and submit tournament entries.
- Organisers review entries, run tournaments, publish fixtures, and record results.
- Admins retain organiser capabilities and control account access roles.

## Visual direction

The approved interface avoids white product-card surfaces and a conventional navigation bar. A basketball trigger opens the court menu. Bebas Neue and Barlow Condensed establish the event-poster hierarchy, while orange, taxi yellow, blue, green, and pink separate routes and information states.

Johannesburg and Pretoria skyline treatments connect the platform to Gauteng. Tar, worn paint, halftone, tyre, brush, and broken-glass layers introduce the physical language of outdoor courts without obscuring the application content.

## Design reference

The PHP implementation follows the approved FINAL D01 to D06 direction from the course Figma workspace:

- D01: node `48:3`
- D02: node `48:124`
- D03: node `48:216`
- D04: node `48:266`
- D05: node `48:345`
- D06: node `48:424`
- Interaction guidance: node `59:3`
- Visual system and moodboard: nodes `21:53` and `21:54`

Final exported and adapted artwork is stored in `assets/images/figma`, `assets/images/official`, and `assets/images/textures` so the repository remains runnable without the design tool.

## Scope and growth

This formative build focuses on a complete local PHP and MySQL workflow. The architecture can later support bracket generation, venue check-in, player statistics, notifications, media publishing, and additional provincial routes without replacing the existing user, team, tournament, entry, and match model.
