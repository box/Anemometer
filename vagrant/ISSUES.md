# Issues found during the migration


```
Installing the Window System drivers
Could not find the X.Org or XFree86 Window System, skipping.
An error occurred during installation of VirtualBox Guest Additions 4.3.36. Some functionality may not work as intended.
In most cases it is OK that the "Window System drivers" installation failed.
Cleaning up downloaded VirtualBox Guest Additions ISO...
```

## Rsync issue

```
There was an error when attempting to rsync a synced folder.
Please inspect the error message below for more info.

Host path: /home/emanuel/vagrant/
Guest path: /home/vagrant/sync
Command: rsync --verbose --archive --delete -z --copy-links --no-owner --no-group --rsync-path sudo rsync -e ssh -p 2222 -o StrictHostKeyChecking=no -o IdentitiesOnly=true -o UserKnownHostsFile=/dev/null -i '/home/emanuel/vagrant/.vagrant/machines/default/virtualbox/private_key' --exclude .vagrant/ /home/emanuel/vagrant/ vagrant@127.0.0.1:/home/vagrant/sync
Error: Warning: Permanently added '[127.0.0.1]:2222' (ECDSA) to the list of known hosts.
symlink has no referent: "/home/emanuel/vagrant/anemometer/anemometer"
FATAL I/O ERROR: dying to avoid a --delete-during issue with a pre-3.0.7 receiver.
rsync error: requested action not supported (code 4) at flist.c(1885) [sender=3.1.0]
```

Forced installation of rsync 3.1.
