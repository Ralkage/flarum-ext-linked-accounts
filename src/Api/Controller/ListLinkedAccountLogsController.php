<?php

namespace Ralkage\LinkedAccounts\Api\Controller;

use Flarum\Api\Controller\AbstractListController;
use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;
use Ralkage\LinkedAccounts\Api\Serializer\LinkedAccountLogSerializer;
use Ralkage\LinkedAccounts\Model\LinkedAccountLog;

class ListLinkedAccountLogsController extends AbstractListController
{
    public $serializer = LinkedAccountLogSerializer::class;

    public $include = ['parentUser', 'childUser'];

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertAdmin();

        $params = $request->getQueryParams();
        $parentUserId = Arr::get($params, 'filter.parentUserId');
        $limit = (int) Arr::get($params, 'page.limit', 50);
        $offset = (int) Arr::get($params, 'page.offset', 0);

        $query = LinkedAccountLog::with(['parentUser', 'childUser'])
            ->orderBy('created_at', 'desc');

        if ($parentUserId) {
            $query->where('parent_user_id', (int) $parentUserId);
        }

        $document->addMeta('total', $query->count());

        return $query->skip($offset)->take($limit)->get();
    }
}
