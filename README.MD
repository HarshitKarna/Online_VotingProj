Hey there, before you look at the code, understand that .zip compression and decompression has changed the spacing and styling of the code inside many times, and i refuse to make it good looking everytime. 
I have tried to important comments so atleast you can understand what block of code does what. Thanks for reading!
Also, I am not that good at styling. I dont even know the style I want. So, I had to take "inspiration" from some modern websites.

---------------------------------------------------------------------------------------

Known issues:

-due to the large size of the CSS file, browser chooses to not reload it everytime you visit the website. So, after making changes to CSS, the result doesnt appear, or in worse cases, CSS disappears completely. This is a browser issue more than issue with this code, but i didnt choose to research further to see if i could tackle it using my code. Anyways, simple fix is CTRL+F5. it force reloads the CSS.

-for emojis, website uses HTML entities/unicodes.
 `&#128499;` → 🗳️ (ballot box)
 `&#9670;` → ◈ (the navbar logo diamond)
 `&#127942;` → 🏆 (gold trophy)
 `&#9889;` → ⚡ (lightning bolt)
 `&#128273;` → 🔑 (key)
 `&#10003;` → ✓ (checkmark)
 `&#9940;` → 🚫 (disqualified)
 ...etc
 Browser's unicode rendering renders these emojis. But, if older browsers are used, the emojis and sometimes dissappear leaving a rectangular box instead.

-(not an issue but) poll's time is calculated independently in each page. i found it hard to make a global running timer that each page could access by referencing. so, all time zones are set to KTM's and time is subtracted in each page separately. doesnt pose a problem, but it does make a block of code redundant in multiple pages.