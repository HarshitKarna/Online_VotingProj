Hey there, this project is made using HTML, CSS, JS (DA BIG BOI HERE), and firebase as database. The authentication is also handled by firebase. Previously, this was a PHP + mySQL project, but it was migrated to firebase and JS so that it can run using the github link: "https://harshitkarna.github.io/VoteNest/"
If you are looking for the PHP + mySQL version: "https://github.com/HKK901/901_VotingProj" (only source code)
Also, not very important but, there is a master admin user id with capability to edit all polls, even if they didnt create it.

---------------------------------------------------------------------------------------------------

Known issues:

-Repository and website made private to fix some security issues.

-For poll options with descriptions, there is "i" icon visible with each option tile while voting. it is supposed to open a window that shows the description. But, as of right now, clicking "i" does nothing. Console ERROR: Uncaught SyntaxError: Unexpected end of input (at view_poll.html?code=48889515:1:34)

-The "Loading Poll" disappears way before the objects actually appear in the page. This causes there to be a ~2 second time delay between loading disappearing and the contents actually appearing. Problem exists in: poll list, viewing poll and dashboard.

------------------------------------------------------------------------------------------------------

Future planned improvements:

-after voting and viewing poll(if allowed), the options in the list have different colors. some golden, some blue, some green. they will be changed to be of same color, but the option we had picked will be highlighted in some way.

-Despite the website having a proper theme, it is not followed in all areas. Will be changed to match the main theme of glassy-neomorphic dark.

-Descriptions are not visible in the dashboard manage page. Will be fixed.

-Comments are lacking HEAVILY. Migration was recent, will take time to add new comments.

-It is not really clear when results are declared and winner has been decided. Only thing separating normally viewing poll progress and result viewing is a trophy. Will be changed to make winner stand out much more.
