<div class="content">

	<div class="ajax_pagination" scrollup="false">
		{!! $group_types->render() !!}
	</div>

	<table class="table table-striped table-hover">
		<thead>
			<tr class="orderable">
				<th column="display_name" type="asc">Display Name</th>
				<th column="name">Name</th>
				<th column="description">Description</th>
				<th column="created_at">Created</th>
				<th column="updated_at">Updated</th>
			</tr>
		</thead>
		<tbody>
			
			@if ($group_types->count())
				@foreach ($group_types as $group_type) 	
				<tr>
					<td> <a href="{{route('group_types.show', $group_type->id) }}"> {{  $group_type->display_name }} </a> </td>
					<td> <a href="{{route('group_types.show', $group_type->id) }}"> {{  $group_type->name }} </a> </td>
					<td> <a href="{{route('group_types.show', $group_type->id) }}"> {{  $group_type->description }} </a> </td>
					<td> {{ $group_type->date("created_at") }} </td>
					<td> {{ $group_type->date("updated_at") }} </td>
				</tr>

				@endforeach
			@else 
				<tr><td colspan="5">@include('includes.no-contents')</td></tr>
			@endif 

		</tbody>
	</table>

	<div class="ajax_pagination" scrollup="true">
		{!! $group_types->render() !!}
	</div>

</div>