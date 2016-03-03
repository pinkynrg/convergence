<?php namespace App\Http\Requests;

use App\Http\Requests\Request;
use Auth;

class UpdateCompanyPersonRequest extends Request {

	/**
	 * Determine if the user is authorized to make this request.
	 *
	 * @return bool
	 */
	public function authorize()
	{
		return Auth::user()->can('update-contact');
	}

	public function forbiddenResponse()
	{
		return Request::get('company_id') == 1 ? redirect()->route('company_person.show',$this->route('id'))->withErrors(['You are not authorized to update employees']) : redirect()->route('company_person.show',$this->route('id'))->withErrors(['You are not authorized to update contacts']);
	}


	/**
	 * Get the validation rules that apply to the request.
	 *
	 * @return array
	 */
	public function rules()
	{
		return [
			'person_id' => 'numeric',
			'department_id' => 'required|numeric', 
			'title_id' => 'required|numeric',
			'phone' => 'numeric',
			'cellphone' => 'numeric',
			'extension' => 'numeric',
			'email' => 'email',
			'group_id' => 'required|numeric'
		];
	}

}
