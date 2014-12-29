<?php
namespace Payum\Core\Bridge\Symfony\Form\Type;

use Payum\Core\PaymentFactoryInterface;
use Payum\Paypal\ExpressCheckout\Nvp\PaymentFactory as PaypalExpressCheckoutPaymentFactory;
use Payum\Stripe\JsPaymentFactory as StripeJsPaymentFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class PaymentConfigType extends AbstractType
{
    /**
     * @var PaymentFactoryInterface[]
     */
    protected $factories = array();

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->factories = array(
            'paypal_express_checkout_nvp' => new PaypalExpressCheckoutPaymentFactory(),
            'stripe_js' => new StripeJsPaymentFactory()
        );

        $builder
            ->add('factory', 'choice', array(
                'choices' => array(
                    'paypal_express_checkout_nvp' => 'Paypal ExpressCheckout',
                    'stripe_js' => 'Stripe.Js',
                )
            ))
        ;

        $builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'buildCredentials'));
        $builder->addEventListener(FormEvents::PRE_SET_DATA, array($this, 'buildCredentials'));
    }

    /**
     * @param FormEvent $event
     */
    public function buildCredentials(FormEvent $event)
    {
        /** @var array $data */
        $data = $event->getData();
        if (false == empty($data['factory'])) {
            return;
        }

        $form = $event->getForm();
        $paymentFactory = $this->factories[$data['factory']];
        $config = $paymentFactory->createConfig();
        foreach ($config['options.default'] as $name => $value) {
            $isRequired = in_array($name, $config['options.required']);
            $form->add($name, is_bool($value) ? 'checkbox' : 'text', array(
                'constraints' => array_filter(array(
                    $isRequired ? new NotBlank : null
                )),
                'empty_data' => $value,
                'required' => $isRequired,
            ));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'payum_payment_config';
    }
}
