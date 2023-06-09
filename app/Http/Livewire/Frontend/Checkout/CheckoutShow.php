<?php

namespace App\Http\Livewire\Frontend\Checkout;

use App\Models\Cart;
use App\Models\Order;
use Livewire\Component;
use App\Models\Orderitem;
use Illuminate\Support\Str;

class CheckoutShow extends Component
{
    public $carts,$totalProductAmount = 0;

    public $fullname,$email,$phone,$pincode,$address;
    public $payment_mode = NULL;

    protected $listeners = [
        'validationForAll'
    ];

    public function validationForAll(){
        $this->validate();
    }

    public function rules(){
        return [
            'fullname' => 'required|string|max:121',
            'email' => 'required|email|max:121',
            'phone' => 'required|numeric|min:8',
            'pincode' => 'required|string|max:6|min:6',
            'address' => 'required|string|max:500',
        ];
    }

    public function placeOrder(){

        $this->validate();

        $order = Order::create([
            'user_id' => auth()->user()->id,
            'tracking_no' => 'alperen-'.Str::random(10),
            'fullname' => $this->fullname,
            'email' => $this->email,
            'phone' => $this->phone,
            'pincode' => $this->pincode,
            'address' => $this->address,
            'status_message' => 'in progress',
            'payment_mode' => $this->payment_mode,
            'payment_id' => $this->payment_id,
        ]);

        foreach($this->carts as $cart){
            $orderItems = Orderitem::create([
                'order_id' => $order->id,
                'product_id' => $cart->product_id,
                'product_color_id' => $cart->product_color_id,
                'quantity' => $cart->quantity,
                'price' => $cart->product->selling_price,
            ]);

            if($cart->product_color_id != NULL){
                $cart->productColor()->where('id',$cart->product_color_id)->decrement('quantity',$cart->quantity);
            }else{
                $cart->product()->where('id',$cart->product_id)->decrement('quantity',$cart->quantity);
            }
        }

        return $order;
    }

    public function codOrder(){

        $this->payment_mode = 'Cash On Delivery';
        $codOrder = $this->placeOrder();
        if($codOrder){
            Cart::where('user_id',auth()->user()->id)->delete();

            session()->flash('message','Your order has been placed successfully');

            $this->dispatchBrowserEvent('message',[
                'message' => 'Order Placed Successfully',
                'type' => 'success',
                'status' => 200
            ]);

            return redirect()->to('thank-you');
        }else{
            $this->dispatchBrowserEvent('message',[
                'message' => 'Order Placed Failed',
                'type' => 'error',
                'status' => 500
            ]);
        }


    }

    public function totalProductAmount(){

        $this->totalProductAmount = 0;
        $this->carts = Cart::where('user_id',auth()->user()->id)->get();

        foreach($this->carts as $cart){
            $this->totalProductAmount += $cart->product->selling_price * $cart->quantity;
        }
        return $this->totalProductAmount;
    }

    public function render()
    {
        $this->fullname = auth()->user()->name;
        $this->email = auth()->user()->email;
        $this->totalProductAmount = $this->totalProductAmount();
        return view('livewire.frontend.checkout.checkout-show',[
            'totalProductAmount' => $this->totalProductAmount,
        ]);
    }
}
