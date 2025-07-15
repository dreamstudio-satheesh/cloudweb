<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SshKeyController extends Controller
{
    private $dummyKeys = [
        [
            'id' => 1,
            'name' => 'MacBook Pro Key',
            'fingerprint' => 'b7:2a:0c:5f:69:d6:4e:f2:18:d3:97:25:09:07:27:e9',
            'public_key' => 'ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABAQ...',
            'created_at' => '2024-01-15 10:30:00',
        ],
        [
            'id' => 2,
            'name' => 'CI/CD Deploy Key',
            'fingerprint' => 'c4:5b:7e:a1:23:f8:90:12:34:56:78:9a:bc:de:f0:12',
            'public_key' => 'ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABAQ...',
            'created_at' => '2024-02-20 14:20:00',
        ],
        [
            'id' => 3,
            'name' => 'Development Machine',
            'fingerprint' => 'a1:b2:c3:d4:e5:f6:78:90:12:34:56:78:9a:bc:de:f0',
            'public_key' => 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIG...',
            'created_at' => '2024-03-10 09:00:00',
        ],
    ];

    public function index()
    {
        $sshKeys = collect($this->dummyKeys)->map(function ($key) {
            return (object) array_merge($key, [
                'created_at' => \Carbon\Carbon::parse($key['created_at']),
            ]);
        });

        return view('ssh-keys.index', compact('sshKeys'));
    }

    public function create()
    {
        return view('ssh-keys.create');
    }

    public function store(Request $request)
    {
        // In production, this would validate and create the SSH key
        return redirect()->route('ssh-keys.index')
            ->with('success', 'SSH key added successfully.');
    }

    public function destroy($id)
    {
        // In production, this would delete the SSH key
        return redirect()->route('ssh-keys.index')
            ->with('success', 'SSH key removed successfully.');
    }
}