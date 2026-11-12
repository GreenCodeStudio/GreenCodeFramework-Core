<?php

namespace Core\Routing;

use Core\Exceptions\NotFoundException;

class McpRouter extends Router
{
    private $responses = [];

    public function findController()
    {
        //doNothing
    }

    var $controllerType = 'Api';

    public function invoke()
    {
        if (explode('?', $this->url)[0] == '/mcp') {
            $request = json_decode(file_get_contents('php://input'));
            if (empty($request->jsonrpc) || $request->jsonrpc != "2.0")
                throw new \Exception('Invalid request');
            if (empty($request->method))
                throw new \Exception('Invalid request');
            try {
                $result = $this->executeMethod($request->method, $request->params);
                $this->responses[] = [
                    'jsonrpc' => '2.0',
                    'result' => $result,
                    'id' => $request->id ?? null
                ];
            } catch (\Throwable $ex) {
                $this->responses[] = [
                    'jsonrpc' => '2.0',
                    'error' => [
                        'code' => -32000,
                        'message' => 'unknown error'
                    ],
                    'id' => $request->id ?? null
                ];
            }
        }
    }

    public function sendBackSuccess()
    {
        header('Content-Type: application/json');
        foreach ($this->responses as $response) {
            echo json_encode($response);
        }
    }

    private function executeMethod($method, $params)
    {
        if ($method == 'server/discover') {
            return [
                "resultType" => "complete",
                "supportedVersions" => ["2026-07-28"],
                "capabilities" => [
                    "tools" => (object)[]
                ],
                "cacheScope" => "private",
                "ttlMs" => 60000,
                "_meta" => [
                    "io.modelcontextprotocol/serverInfo" => [
                        "name" => "app",
                        "version" => "1.0.0"
                    ]
                ],
            ];
        } else if ($method == 'tools/list') {
            $tools = [];
            $controllers = (new ApiRouter())->listControllers();
            foreach ($controllers as $controller) {
                foreach ($controller->methods as $method) {
                    foreach ($method->annotations as $annotation) {
                        if ($annotation instanceof \ApiEndpointAnnotation || $annotation instanceof \ApiEndpoint) {
                            $tools[] = [
                                "name" => $controller->name.'/'.$method->name,
                                "title" => $controller->name.'/'.$method->name,
                                "description" => $annotation->description ?? "",
                                "inputSchema" => [
                                    'type' => 'object',
                                    'properties' => (object)[
                                    ]
                                ],
//                                "annotations" => [
//                                    "readOnlyHint" => true,
//                                    "destructiveHint" => false,
//                                    "idempotentHint" => true,
//                                    "openWorldHint" => false
//                                ],
                            ];
                        }
                    }
                }
            }
            return [
                "resultType" => "complete",
                'ttlMs' => 600000,
                'cacheScope' => 'private',
                "tools" => $tools
            ];
        } else if ($method == 'tools/call') {
            [$this->controllerName, $this->methodName] = explode('/', $params->name);
            $this->args=[];
            $this->prepareController();
            $this->prepareMethod();
            $this->runMethod();
            return [
                "resultType" => "complete",
                "content" => [],
                "structuredContent" => [
                    "notebooks" => [
                        [
                            "name" => "notebook1",
                            "title" => "Notebook 1",
                            "description" => "This is notebook 1"
                        ],
                        [
                            "name" => "notebook2",
                            "title" => "Notebook 2",
                            "description" => "This is notebook 2"
                        ]
                    ]
                ]
            ];
        } else {
            throw new NotFoundException();
        }
    }
}
