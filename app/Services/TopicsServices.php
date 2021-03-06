<?php
/**
 * Created by PhpStorm.
 * User: dongyuxiang
 * Date: 2017/7/23
 * Time: 23:32
 */

namespace App\Services;


use App\Models\Topic;
use Illuminate\Support\Facades\Input;

class TopicsServices
{
    /**
     * 创建帖子
     * @param $params
     * @return mixed
     */
    public function storeTopic($params)
    {
        $params['user_id'] = \Auth::id();

        $model = new Topic($params);
        $model->save();
        $topicId = $model->getQueueableId();

        return $topicId;
    }

    /**
     * 删除帖子
     * @param $id
     * @param bool $force
     * @return bool|null
     */
    public function destroyTopic($id, $force = false)
    {
        $model = app(Topic::class);
        $topic = $model->find($id);

        if ($force) {
           $result = $topic->forceDelete();
        } else {
            $result = $topic->delete();
        }

        return $result;
    }

    /**
     * 更新帖子
     * @param $id
     * @param $params
     * @return bool
     */
    public function updateTopic($id, $params)
    {
        $model = app(Topic::class);
        $topic = $model->find($id);

        $result = $topic->update($params);

        return $result;
    }


    /**
     * 获取帖子
     * @param $id
     * @return \Illuminate\Database\Eloquent\Model|static
     */
    public function getTopic($id)
    {
        $model = app(Topic::class);

        $result = $model::where('id', $id)->with('user')->firstOrFail();

        return $result;
    }

    /**
     * 获取帖子回复列表
     * @param Topic $topic
     * @param string $order
     * @param int $limit
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getTopicRepliesWithLimit(Topic $topic, $order = 'created_at', $limit = 30)
    {
        $pageName = 'page';
        // Default display the latest reply
        $latest_page = is_null(Input::get($pageName)) ? ceil($topic->reply_count / $limit) : 1;
        $query = $topic->replies()->with('user');

        $query = ($order == 'vote_count') ? $query->orderBy('vote_count', 'desc') : $query->orderBy('created_at', 'asc');

        return $query->paginate($limit, ['*'], $pageName, $latest_page);
    }

    /**
     * 获取分类列表
     * @param $filter
     * @param $category_id
     * @param int $perPage
     * @return mixed
     */
    public function getCategoryTopicsWithFilter($filter, $category_id, $perPage = 15)
    {
        $filter = $this->getTopicFilter($filter);

        return $this->applyFilter($filter)
            ->where('category_id', '=', $category_id)
            ->with('user', 'category', 'lastReplyUser')
            ->paginate($perPage);

    }

    /**
     * 获取类别分页数据
     * @param $filter
     * @param int $perPage
     * @return mixed
     */
    public function getTopicsWithFilter($filter, $perPage = 15)
    {
        $filter = $this->getTopicFilter($filter);

        return $this->applyFilter($filter)
            ->with('user', 'category', 'lastReplyUser')
            ->paginate($perPage);

    }

    /**
     * 分类过滤器安全性检查
     * @param $request_filter
     * @return string
     */
    private function getTopicFilter($requestFilter)
    {
        $filters = ['no-reply', 'vote', 'excellent', 'recent', 'excellent-pinned', 'index'];
        if (in_array($requestFilter, $filters)) {
            return $requestFilter;
        }
        return 'default';
    }

    /**
     * 根据过滤取数据
     * @param $filter
     * @return $this
     */
    private function applyFilter($filter)
    {
        $query = app(Topic::class);

        switch ($filter) {
            case 'no-reply':
                return $query->orderBy('order', 'desc')->orderBy('reply_count', 'asc')->orderBy('created_at', 'desc');
                break;
            case 'vote':
                return $query->orderBy('order', 'desc')->orderBy('vote_count', 'desc')->orderBy('created_at', 'desc');
                break;
            case 'excellent':
                return $query->where('is_excellent', true)->orderBy('created_at', 'desc');
                break;
            case 'recent':
                return $query->orderBy('order', 'desc')->orderBy('created_at', 'desc');
                break;
            case 'index':
            default:
                return $query->orderBy('order', 'desc')->orderBy('updated_at', 'desc')->orderBy('created_at', 'desc');
                break;
        }
    }


}