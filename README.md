# Time-sensitive state machine

This library allows you to build a state machine whose state depends
on the current time.

## What's it for?

If you have components that are supposed to automatically change
behaviour based on time, this is for you.

## Example use case:

A competition that accepts entries during a certain interval, but must
reject entries otherwise. Say this competition then has a voting stage
after the entries have been submitted, and once the voting has finished
it will pick winners and display them.

## Caveats

* Overlaps

  It it recommended that you don't have overlapping intervals.

  This library doesn't check for overlaps. If your states overlap
  the behaviour is undefined. In the future the library may check
  and forbid overlaps.

* Gaps

  It is recommended that you don't have gaps between intervals.
