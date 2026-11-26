<x-form.row
    :label="trans('asset-returns.warehouse_edit')"
    name="warehouse_location_id"
    :help_text="trans('asset-returns.warehouse_edit_help')"
>
    <x-slot:input>
        <x-input.select
            :native="true"
            id="warehouse_location_id"
            name="warehouse_location_id"
            :selected="old('warehouse_location_id')"
            :options="['' => trans('asset-returns.warehouse_edit_keep')] + \App\Services\AssetReturns::warehouseEditOptions()->all()"
        />
    </x-slot:input>
</x-form.row>
