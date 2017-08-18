<?php

namespace Context;

require(__DIR__ . '/../vendor/autoload.php');

$path = parse_url($_SERVER['REQUEST_URI'], \PHP_URL_PATH);
$path = explode('/', trim($path, '/'));

if (!empty($path)) {
    $peek = $path[0];
    switch(array_shift($path)) {
        case 'part': {
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                $filter = array_shift($path) ?? 'html';
                $content = file_get_contents('php://input');
                $name = sha1($filter . sha1($content));
                if (!preg_match('|[a-z0-9-]+|', $filter)) {
                    http_response_code(400);
                } else {
                    $filterClass = '\\' . __NAMESPACE__ . '\\' . ucfirst($filter) . 'Filter';
                    if (!class_exists($filterClass)) {
                        http_response_code(404);
                    } else {
                        try {
                            $filter = new $filterClass($content, ...$path);
                            if (file_put_contents(__DIR__ . "/../cache/$name.json", $filter->render()) === false) {
                                throw new \Exception(error_get_last()['message']);
                            }
                            http_response_code(201);
                            echo($name);
                        } catch (\Throwable $e) {
                            http_response_code(500);
                        }
                    }
                }
                break;
            }
        }
        default:
            if (preg_match('/^([0-9a-f]{40})\.([a-z0-9]+)$/', $peek, $matches)) {
                $extensionMap = ['md' => 'markdown'];
                $name = $matches[1];
                $extension = $matches[2];
                $partFile = __DIR__ . "/../cache/$name.json";
                if (array_key_exists($extension, $extensionMap)) {
                    $classExtension = $extensionMap[$extension];
                } else {
                    $classExtension = $extension;
                }
                $renderClass = '\\' . __NAMESPACE__ . '\\' . ucfirst($classExtension) . 'Render';
                if (is_file($partFile) && class_exists($renderClass)) {
                    try {
                        $render = new $renderClass(file_get_contents($partFile), ...$path);
                        $output = $render->render(...$path);
                        header('Content-Type: ' . $render->getMimeType());
                        header("Content-Disposition: attachment; filename=\"$peek.$extension\"");
                        echo($output);
                    } catch (\Throwable $e) {
                        echo($e);
                        http_response_code(500);
                    }
                } else {
                    http_response_code(404);
                }
            } else {
                http_response_code(404);
            }
    }
} else {
    http_response_code(302);
    header('Location: https://github.com/erayd/context');
}
