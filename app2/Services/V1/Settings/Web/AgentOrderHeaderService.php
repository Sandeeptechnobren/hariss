<?php

namespace App\Services\V1\Settings\Web;

use App\Models\AgentOrderHeader;

class AgentOrderHeaderService
{
    public function list()
    {
        return AgentOrderHeader::paginate(15);
    }

    public function create(array $data): AgentOrderHeader
    {
        return AgentOrderHeader::create($data);
    }

    public function show(int $id): AgentOrderHeader
    {
        return AgentOrderHeader::findOrFail($id);
    }

    public function update(int $id, array $data): AgentOrderHeader
    {
        $order = AgentOrderHeader::findOrFail($id);
        $order->update($data);
        return $order;
    }

    public function delete(int $id): bool
    {
        return AgentOrderHeader::destroy($id) > 0;
    }
}
