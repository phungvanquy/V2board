<?php

namespace App\Http\Controllers\V1\Admin\Server;

use App\Http\Controllers\Controller;
use App\Models\ServerVless;
use Illuminate\Http\Request;
use ParagonIE_Sodium_Compat as SodiumCompat;
use App\Utils\Helper;

class VlessController extends Controller
{
    public function save(Request $request)
    {
        $params = $request->validate([
            'group_id' => 'required',
            'route_id' => 'nullable|array',
            'name' => 'required',
            'parent_id' => 'nullable|integer',
            'host' => 'required',
            'port' => 'required',
            'server_port' => 'required',
            'tls' => 'required|in:0,1,2',
            'tls_settings' => 'nullable|array',
            'flow' => 'nullable|in:xtls-rprx-vision',
            'network' => 'required',
            'network_settings' => 'nullable|array',
            'tags' => 'nullable|array',
            'rate' => 'required',
            'show' => 'nullable|in:0,1',
            'sort' => 'nullable'
        ]);

        if (isset($params['tls']) && (int)$params['tls'] === 2) {
            $keyPair = SodiumCompat::crypto_box_keypair();
            $params['tls_settings'] = $params['tls_settings'] ?? [];
            if (!isset($params['tls_settings']['public_key'])) {
                $params['tls_settings']['public_key'] = Helper::base64EncodeUrlSafe(SodiumCompat::crypto_box_publickey($keyPair));
            }
            if (!isset($params['tls_settings']['private_key'])) {
                $params['tls_settings']['private_key'] = Helper::base64EncodeUrlSafe(SodiumCompat::crypto_box_secretkey($keyPair));
            }
            if (!isset($params['tls_settings']['short_id'])) {
                $params['tls_settings']['short_id'] = substr(sha1($params['tls_settings']['private_key']), 0, 8);
            }
            if (!isset($params['tls_settings']['server_port'])) {
                $params['tls_settings']['server_port'] = "443";
            }
        }

        if ($request->input('id')) {
            $server = ServerVless::find($request->input('id'));
            if (!$server) {
                abort(500, 'Server does not exist');
            }
            try {
                $server->update($params);
            } catch (\Exception $e) {
                abort(500, 'Save failed');
            }
            return response([
                'data' => true
            ]);
        }

        if (!ServerVless::create($params)) {
            abort(500, 'Creation failed');
        }

        return response([
            'data' => true
        ]);
    }

    public function drop(Request $request)
    {
        if ($request->input('id')) {
            $server = ServerVless::find($request->input('id'));
            if (!$server) {
                abort(500, 'Node ID does not exist');
            }
        }
        return response([
            'data' => $server->delete()
        ]);
    }

    public function update(Request $request)
    {
        $params = $request->validate([
            'show' => 'nullable|in:0,1',
        ]);

        $server = ServerVless::find($request->input('id'));

        if (!$server) {
            abort(500, 'This server does not exist');
        }
        try {
            $server->update($params);
        } catch (\Exception $e) {
            abort(500, 'Save failed');
        }

        return response([
            'data' => true
        ]);
    }

    public function copy(Request $request)
    {
        $server = ServerVless::find($request->input('id'));
        $server->show = 0;
        if (!$server) {
            abort(500, 'Server does not exist');
        }
        if (!ServerVless::create($server->toArray())) {
            abort(500, 'Copy failed');
        }

        return response([
            'data' => true
        ]);
    }
}
