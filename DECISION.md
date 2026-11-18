# DECISIONS

Short log of design and scope decisions. Add one row per change. Newest at the bottom.
Format: date, area, decision, reason.

## Phase 9: Resident reporting form

| Date | Decision | Reason |
| ---- | -------- | ------ |
| 2026-11-16 | Cut the form from 7 steps to 4: Problem, Location, Photo and updates, Review and send | Proposal only requires description, photo, and location. FixMyStreet uses a similar short flow. |
| 2026-11-16 | Emergency is a persistent red shortcut strip, not a numbered "Type" step. Tapping it replaces the form with the emergency screen | Most residents file community concerns, so a mandatory first step adds friction. Emergency still goes straight to 911 before any form fields. |
| 2026-11-16 | Emergency screen shows only contacts with a number, one 911 button, tap-to-call, and no duplicate 911 | Four of seven cards were "N/A" and 911 appeared twice. |
| 2026-11-16 | Required fields are only category, description, one location method, and consent | Matches the proposal. Name, email, mobile, photo, and situation answers are optional. |
| 2026-11-16 | No preselected answers on safety or situation questions. One visible question ("anyone in danger right now?"); the rest sit under "More details (optional)" | A default "No" on a safety question can record false data. Fewer visible questions per screen. |
| 2026-11-16 | Contact step simplified: removed the "Preferred Contact Method" radios, mobile is no longer required, anonymous hides the contact fields. `preferred_contact` is derived | Required mobile contradicted the proposal and the "None" option. SMS is out of scope, so "Phone / SMS" was misleading. |
| 2026-11-16 | Map is centered and bounded on Barangay Calarian, taller, with "Use my current location" on the map | The map opened on Yubengco Starmall. The button sat below the map. |
| 2026-11-16 | Nearby-reports check ("Is this the same problem?") appears after the pin is placed | Reduces duplicates early. Already specified in Phase 9. |
| 2026-11-16 | Report page uses a compact header instead of the full hero | The hero pushed the form below the fold on phones. |

## Unchanged on purpose

- Database schema, domain events, and contracts from Phase 8.
- AI suggests, rules route, officials decide. The AI never replaces the resident's category choice.
- Tracking code, no resident account, consent via `consent_at`.

## Deviation from the original Phase 9 text

- The old "Report type step first" and the separate "Situation" and "Contact" steps are replaced by the changes above. The intent (emergency goes straight to 911) is kept.