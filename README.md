# EtG-CIA_Tool

## Phase 1 - Crucible Candidates 
**_Completed_**

* Move card threads into Crucible subforum
* Add card names to appropriate poll
* Add card images to first post of appropriate thread
* Remove card images from "Link Crucible Candidate" thread, removing post if no other card images left
* Archive cards that didn't receive enough votes

## Phase 2 - Promote Crucible 
**_Completed_**

* Move card threads into Forge subforum
* Add card names to appropriate poll
* Add card images to first post of appropriate thread
* Remove card images from first post of appropriate thread
* Archive cards that didn't receive enough votes
* Generate code for CC to make "Promoted" post in appropriate threads.

## Phase 3 - Promote Forge 
**_In Progress_**

* Move card threads into Armory
* Add card images to first post of appropriate thread
* Remove card images from first post of appropriate thread
* Archive cards that didn't receive enough votes
* Generate code for CC to make "Promoted" post in appropriate threads

## Phase 4 - FG Proposals 
**_Not started_**

Not sure on this one yet. At this point this process wouldn't be able to be used in a way to actually save any time. This might need to wait for a future version of the app, if it's implemented at all. Open to any ideas on how to automate this part of the process.

---

### To Use

Add a config.db.php file with $baseUrl (full http path to forums), $db["username"], $db["password"], and $db["database"] specified.