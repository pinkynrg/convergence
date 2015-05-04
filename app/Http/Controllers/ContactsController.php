<?php namespace Convergence\Http\Controllers;

use Input;
use Convergence\Models\Contact;
use Convergence\Models\Customer;
use Convergence\Http\Requests\CreateContactRequest;
use Convergence\Http\Requests\UpdateContactRequest;
use Form;

class ContactsController extends Controller {

	public function index() {
        $data['contacts'] = Contact::paginate(50);
        $data['title'] = "Convergence - Contacts";
        return view('contacts/index', $data);
	}

	public function create($id) {
		$data['customer'] = Customer::find($id);
		$data['customer']->customer_id = $data['customer']->id;
		return view('contacts/create', $data);
	}

	public function store(CreateContactRequest $request) {
		Contact::create($request->all());
		$customer_id = Input::get('customer_id');
		return redirect()->route('customers.show',$customer_id);
	}

	public function show($id) {
		$data['menu_actions'] = [Form::editItem(route('contacts.edit',$id), 'Edit this contact'),
			Form::deleteItem('contacts.destroy',$id, 'Delete this contact')];
        $data['contact'] = Contact::find($id);
        return view('contacts/show', $data);
	}

	public function edit($id) {
        $data['contact'] = Contact::find($id);
        return view('contacts/edit', $data);        
	}

	public function update($id, UpdateContactRequest $request) {
		$contact = Contact::find($id);
		$contact->update($request->all());
		return redirect()->route('contacts.show', $contact->id);
	}

	public function destroy($id) {
		$contact = Contact::find($id);
		$contact->delete();
		return redirect()->route('customers.show',$contact->customer_id);
	}

	public function ajaxContactsRequest() {
        $data['contacts'] = Contact::paginate(50);
        return view('contacts/contacts',$data);
    }

}