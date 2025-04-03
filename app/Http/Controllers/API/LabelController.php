<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\LabelRequest;
use App\Repositories\LabelRepository;

class LabelController extends Controller
{
    protected $labelRepository;
    
    public function __construct(
        LabelRepository $labelRepository
    )
    {
        $this->labelRepository = $labelRepository;
    }

    public function index()
    {
        $listLabels = $this->labelRepository->listLabels();
        return response()->json([
            'success' => true,
            'message' => 'Lấy danh sách list labels thành công',
            'data' => $listLabels
        ], 200);
    
    }

    public function store(LabelRequest $request)
    {
        try {
            $input = $input = $request->except(['_token']);
            $dataLabel = $this->labelRepository->createLabel( $input);
            
            return response()->json([
                'success' => true,
                'data' => $dataLabel,
                'message' => 'Tạo label thành công',
                'type' => 'create_label_success',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tạo list',
                'type' => 'error_create_label',
                'error' => $e->getMessage()
            ], 500);
        }
        
    }

    public function show($id)
    {
        $label = $this->labelRepository->show($id);
        if(!$label) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy label',
                'type' => 'label_not_found',
            ], 404);
        }
        return response()->json([
            'success' => true,
            'workspace' => $label,
            'message' => 'Thông tin label',
            'type' => 'label_information',
        ], 201);
    }

    public function update(LabelRequest $request, $id)
    {
        try {
            $input = $input = $request->except(['_token']);
            $label = $this->labelRepository->show($id);
            if(!$label) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy label',
                    'type' => 'label_not_found',
                ], 404);
            }
            $dataLabel = $this->labelRepository->updateLabel($input, $id);
        
            return response()->json([
                'success' => true,
                'data' => $dataLabel,
                'message' => 'Update label thành công',
                'type' => 'update_label_success',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi update label ',
                'type' => 'error_update_label',
                'error' => $e->getMessage()
            ], 500);
        }
        
    }
  
    public function destroy($id)
    {
        $label = $this->labelRepository->show($id);
        if(!$label) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy label',
                'type' => 'label_not_found',
            ], 404);
        }
        
        $this->labelRepository->destroy($id);
        return response()->json([
            'success' => true,
            'workspace' => $label,
            'message' => 'Thông tin label được xóa',
            'type' => 'delete_label_success',
        ], 201);
    }

}
