<?php

namespace Core\Routing;

class WellKnownRouter extends Router
{
    private $response = [];

    public function findController()
    {
        //doNothing
    }

    public function invoke()
    {
        if (explode('?', $this->url)[0] == '/.well-known/oauth-protected-resource') {
            $this->response = [
                'resource' => 'https://'.$_SERVER['HTTP_HOST'].'/',
                'resource_name' => 'App',
                'authorization_servers' => [
                    'https://'.$_SERVER['HTTP_HOST'].'/oauth2',
                    "bearer_methods_supported" => ["header"],
                ]
            ];
        }
        if (str_starts_with($this->url, '/.well-known/oauth-authorization-server')) {
            $this->response = [
                'issuer' => 'https://'.$_SERVER['HTTP_HOST'].'/',
                'authorization_endpoint' => 'https://'.$_SERVER['HTTP_HOST'].'/oauth2/authorize',
                'registration_endpoint' => 'https://'.$_SERVER['HTTP_HOST'].'/oauth2/register',
                'service_documentation' => 'https://'.$_SERVER['HTTP_HOST'].'/oauth2/documentation',
                'token_endpoint' => 'https://'.$_SERVER['HTTP_HOST'].'/oauth2/token',
                'response_types_supported'=>[
                    'code'
                ]
            ];
        }
        if (str_starts_with($this->url, '/.well-known/ard.json')) {
            $this->response = [
                'entries' => [
                    [
                        "identifier" => "urn:air:".$_SERVER['HTTP_HOST'].":server:weather",
                        "displayName" => "Acme Weather Telemetry Server",
                        "type" => "application/mcp-server+json",
                        "url" => "https://".$_SERVER['HTTP_HOST']."/mcp",
                        "capabilities" => ["test"],
                        "description" => "Test mcp.",
                        "representativeQueries" => [
                            "todo",
                            "todo"
                        ]
                    ]
                ]
            ];
        }
        if (str_starts_with($this->url, '/.well-known/agent-card.json')) {
            $this->response = [

                "name" => "App",
                "description" => "",
                "supportedInterfaces" => [
                    [
                        "url" => "https://".$_SERVER['HTTP_HOST']."/mcp",
                        "protocolBinding" => "JSONRPC",
                        "protocolVersion" => "1.0"
                    ]
                ],
                "version" => "1.0.0",
                "capabilities" => [
                    "streaming" => false,
                    "pushNotifications" => false,
                    "extensions" => []
                ],
                "defaultInputModes" => ["text/plain"],
                "defaultOutputModes" => ["text/plain"],
                "skills" => [
                ]

            ];
        }
    }

    public function sendBackSuccess()
    {
        header('Content-Type: application/json');
        echo json_encode($this->response);
    }
}
