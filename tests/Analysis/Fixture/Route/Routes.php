<?php
namespace Tests\Analysis\Fixture\Route;

use Framework\Discovery\Attr\Route;
use Framework\IO\Request;
use Framework\IO\Response;
use Framework\System\Access;

class Routes {

    #[Route("/boxes/create", Access::General)]
    public static function create(Request $request): Response {
        return Response::success("CREATED", [ "id" => $request->getInt("id") ]);
    }

    #[Route("/boxes/edit", Access::General)]
    public static function renamed(Request $request): Response {
        return Response::success("EDITED", [ "id" => $request->getInt("id") ]);
    }

    #[Route("/boxes/view", Access::General)]
    public static function view(Request $request): string {
        return $request->getString("id");
    }

    #[Route("/boxes/remove", Access::General)]
    public static function remove(int $id): Response {
        return Response::success("REMOVED", [ "id" => $id ]);
    }
}
