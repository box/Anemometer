## Migrated to Centos

https://github.com/mitchellh/vagrant/issues/6497

Adding:

```
config.vm.synced_folder ".", "/vagrant", type: "nfs"
```

I thought it was: https://www.virtualbox.org/ticket/12879


Solution was:

vagrant plugin install vagrant-vbguest


