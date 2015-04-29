# -*- mode: ruby -*-
# vi: set ft=ruby :

Vagrant.configure("2") do |config|
	config.vm.box = "puppetlabs/centos-6.5-64-puppet"
	config.vm.provision "puppet" do |puppet|
		puppet.manifests_path = "puppet/manifests"
		puppet.manifest_file  = "site.pp"
		puppet.module_path    = "puppet/modules"
	end
	config.vm.provider "virtualbox" do |vb|
		vb.customize ["modifyvm",:id,"--memory","2048"]
	end
	config.vm.define "local" do |local|
		local.vm.network "private_network", ip: "10.0.5.2"
		local.vm.provider "virtualbox" do |vb|
			vb.name = "local"
		end
	end
	config.vm.define "remote-1" do |remote_1|
		remote_1.vm.network "private_network", ip: "10.0.5.3"
		remote_1.vm.provider "virtualbox" do |vb|
			vb.name = "remote-1"
		end
	end
end