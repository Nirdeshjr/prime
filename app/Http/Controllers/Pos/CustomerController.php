<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customer;
use Auth;
use Illuminate\Support\Carbon;
use Image;
use App\Models\Payment;
use App\Models\PaymentDetail;
use App\Models\Sender;
use App\Models\Receiver;
use App\Models\Box;
use App\Models\Shipment;

class CustomerController extends Controller
{
    public function CustomerAll()
    {

        $senders = Sender::select('id', 'senderName', 'senderPhone', 'senderEmail', 'senderAddress',)->get();
        return view('backend.customer.customer_all', compact('senders'));
    } // End Method















    public function CustomerAdd()
    {
        return view('backend.customer.customer_add');
    }    // End Method





    public function CustomerEdit($id)
    {

        $customer = Customer::findOrFail($id);
        return view('backend.customer.customer_edit', compact('customer'));
    } // End Method


    public function CustomerUpdate(Request $request)
    {

        $customer_id = $request->id;
        if ($request->file('customer_image')) {

            $image = $request->file('customer_image');
            $name_gen = hexdec(uniqid()) . '.' . $image->getClientOriginalExtension(); // 343434.png
            Image::make($image)->resize(200, 200)->save('upload/customer/' . $name_gen);
            $save_url = 'upload/customer/' . $name_gen;

            Customer::findOrFail($customer_id)->update([
                'name' => $request->name,
                'mobile_no' => $request->mobile_no,
                'email' => $request->email,
                'address' => $request->address,
                'customer_image' => $save_url,
                'updated_by' => Auth::user()->id,
                'updated_at' => Carbon::now(),

            ]);

            $notification = array(
                'message' => 'Customer Updated with Image Successfully',
                'alert-type' => 'success'
            );

            return redirect()->route('customer.all')->with($notification);
        } else {

            Customer::findOrFail($customer_id)->update([
                'name' => $request->name,
                'mobile_no' => $request->mobile_no,
                'email' => $request->email,
                'address' => $request->address,
                'updated_by' => Auth::user()->id,
                'updated_at' => Carbon::now(),

            ]);

            $notification = array(
                'message' => 'Customer Updated without Image Successfully',
                'alert-type' => 'success'
            );

            return redirect()->route('customer.all')->with($notification);
        } // end else

    } // End Method


    public function CustomerDelete($id)
    {

        $customers = Sender::findOrFail($id);


        Sender::findOrFail($id)->delete();

        $notification = array(
            'message' => 'Customer Deleted Successfully',
            'alert-type' => 'success'
        );

        return redirect()->back()->with($notification);
    } // End Method
    public function CreditCustomer()
    {
        $allData = Payment::whereIn('paid_status', ['full_paid', 'partial_paid'])->get();
        return view('backend.customer.customer_credit', compact('allData'));
    }

    public function CreditCustomerPrintPdf()
    {
        $allData = Payment::whereIn('paid_status', ['full_paid', 'partial_paid'])->get();
        return view('backend.pdf.customer_credit_pdf', compact('allData'));
    }

    public function CustomerEditInvoice($invoice_id)
    {
        $payment = Payment::where('invoice_id', $invoice_id)->first();
        return view('backend.customer.edit_customer_invoice', compact('payment'));
    }
    public function CustomerUpdateInvoice(Request $request, $invoice_id)
    {
        if ($request->new_paid_amount < $request->paid_amount) {
            $notification = array(
                'message' => 'Sorry you paid maximum value',
                'alert-type' => 'error'
            );

            return redirect()->back()->with($notification);
        } else {
            $payment = Payment::where('invoice_id', $invoice_id)->first();
            $payment_details = new PaymentDetail();
            $payment->paid_status = $request->paid_status;

            if ($request->paid_status == 'full_paid') {
                $payment->paid_amount = Payment::where('invoice_id', $invoice_id)->first()['paid_amount'] + $request->new_paid_amount;
                $payment->due_amount = '0';
                $payment_details->current_paid_amount = $request->new_paid_amount;
            } elseif ($request->paid_status == 'partial_paid') {
                $payment->paid_amount = Payment::where('invoice_id', $invoice_id)->first()['paid_amount'] + $request->paid_amount;
                $payment->due_amount = Payment::where('invoice_id', $invoice_id)->first()['due_amount'] - $request->paid_amount;
                $payment_details->current_paid_amount = $request->paid_amount;
            }
            $payment->save();
            $payment_details->invoice_id = $invoice_id;
            $payment_details->date = date('Y-m-d', strtotime($request->date));
            $payment_details->updated_by = Auth::user()->id;
            $payment_details->save();

            $notification = array(
                'message' => 'Invoice Update Successfully',
                'alert-type' => 'success'
            );
            return redirect()->route('credit.customer')->with($notification);
        }
    }
    public function CustomerInvoiceDetails($invoice_id)
    {
        $payment = Payment::where('invoice_id', $invoice_id)->first();
        return view('backend.pdf.invoice_details_pdf', compact('payment'));
    }
    public function PaidCustomer()
    {
        $allData = Payment::where('paid_status', '!=', 'full_due')->get();
        return view('backend.customer.customer_paid', compact('allData'));
    }
    public function PaidCustomerPrintPdf()
    {
        $allData = Payment::where('paid_status', '!=', 'full_due')->get();
        return view('backend.pdf.customer_paid_pdf', compact('allData'));
    }
    public function CustomerWiseReport()
    {
        $customers = Customer::all();
        return view('backend.customer.customer_wise_report', compact('customers'));
    }
    public function CustomerWiseCreditReport(Request $request)
    {
        $allData = Payment::where('customer_id', $request->customer_id)->whereIn('paid_status', ['full_due', 'partial_paid'])->get();
        return view('backend.pdf.customer_wise_credit_pdf', compact('allData'));
    }
    public function CustomerWisePaidreport(Request $request)
    {
        $allData = Payment::where('customer_id', $request->customer_id)->where('paid_status', '!=', 'full_due')->get();
        return view('backend.customer.customer_wise_paid_pdf', compact('allData'));
    }





    public function CustomerStored(Request $request)
    {
       
        // dd($request->all());

        try {
            // Store sender details
            $sender =  Sender::create([
                'senderName' => $request->senderName,
                'senderPhone' => $request->senderPhone,
                'senderEmail' => $request->senderEmail,
                'senderAddress' => $request->senderAddress,

            ]);
            $sender_id = $sender->id;


            Receiver::create([
                'sender_id' => $sender_id,
                'receiverName' => $request->receiverName,
                'receiverPhone' => $request->receiverPhone,
                'receiverEmail' => $request->receiverEmail,
                'receiverAddress' => $request->receiverAddress,
                'receiverPostalcode' => $request->receiverPostalcode, // Accessing receiver postal code
                'receiverCountry' => $request->receiverCountry,

            ]);
            
            Shipment::create([
                'senders_id' => $sender_id,
                'shipment_via' => $request->shipment_via,
                'actual_weight' => $request->actual_weight,
                'invoice_date' => $request->invoice_date,
                'dimension' => $request->dimension,

            ]);


            // foreach ($request->boxes as $boxData) {
            //     // Create a new box and store it in the database
            //     Box::create([
            //         'sender_id' => $request->sender_id,
            //         'box_number' => $boxData['box_number'],
            //     ]);
            // }


            return redirect()->route('customer.all')->with('error', 'An error occurred while saving the data.');
        } catch (\Exception $e) {
            return redirect()->route('customer.all')->with('error', 'An error occurred while saving the data.');
        }
    }




    public function CustomerStore(Request $request)
{
    try {
        // Step 1: Split the form data for sender, receiver, and shipment
        $senderData = [
            'senderName' => $request->senderName,
            'senderPhone' => $request->senderPhone,
            'senderEmail' => $request->senderEmail,
            'senderAddress' => $request->senderAddress,
        ];

        $receiverData = [
            'receiverName' => $request->receiverName,
            'receiverPhone' => $request->receiverPhone,
            'receiverEmail' => $request->receiverEmail,
            'receiverAddress' => $request->receiverAddress,
            'receiverPostalcode' => $request->receiverPostalcode, // Accessing receiver postal code
            'receiverCountry' => $request->receiverCountry,
        ];

        $shipmentData = [
            'shipment_via' => $request->shipment_via,
            'actual_weight' => $request->actual_weight,
            'invoice_date' => $request->invoice_date,
            'dimension' => $request->dimension,
        ];

        // Step 2: Insert sender data into the 'senders' table
        $sender = Sender::create($senderData);

        // Retrieve the sender_id
        $sender_id = $sender->id;   

        // Step 3: Insert into 'receiver' and 'shipment' tables using sender_id
        $receiverData['sender_id'] = $sender_id;
        // dd($receiverData);
        Receiver::create($receiverData);
       

        $shipmentData['senders_id'] = $sender_id;
        

        Shipment::create($shipmentData);
        dd($shipmentData);

        // Redirect or return a success message
        return redirect()->route('customer.all')->with('success', 'Data saved successfully.');
    } catch (\Exception $e) {
        // Handle errors, if any
        return redirect()->route('customer.all')->with('error', 'An error occurred while saving the data.');
    }
}

}
