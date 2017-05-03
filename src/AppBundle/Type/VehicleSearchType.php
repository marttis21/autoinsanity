<?php

namespace AppBundle\Type;

use AppBundle\Entity\Brand;
use AppBundle\Entity\City;
use AppBundle\Entity\Color;
use AppBundle\Entity\Country;
use AppBundle\Entity\FuelType;
use AppBundle\Entity\Model;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VehicleSearchType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $modelModifier = function (FormInterface $form, Brand $brand = null) {
            $form->add('model', EntityType::class, [
                'class' => Model::class,
                'label' => 'form.field.model',
                'placeholder' => 'form.placeholder.all.model',
                'query_builder' => function (EntityRepository $repo) use ($brand) {
                    return $repo->createQueryBuilder('model')
                        ->where('model.brand = :brand')
                        ->setParameter('brand', $brand === null ? null : $brand->getId())
                        ->orderBy('model.name', 'ASC');
                },
                'required' => false,
            ]);
        };
        $cityModifier = function (FormInterface $form, Country $country = null) {
            $form->add('city', EntityType::class, [
                'class' => City::class,
                'label' => 'form.field.city',
                'placeholder' => 'form.placeholder.all.city',
                'query_builder' => function (EntityRepository $repo) use ($country) {
                    return $repo->createQueryBuilder('city')
                        ->where('city.country = :country')
                        ->setParameter('country', $country === null ? null : $country->getId())
                        ->orderBy('city.name', 'ASC');
                },
                'required' => false,
            ]);
        };
        $builder
            ->setMethod('GET')
            ->add('brand', EntityType::class, [
                'class' => Brand::class,
                'label' => 'form.field.brand',
                'placeholder' => 'form.placeholder.all.brand',
                'query_builder' => function (EntityRepository $repo) {
                    return $repo->createQueryBuilder('brand')->orderBy('brand.name', 'ASC');
                },
                'required' => false,
            ])
            ->add('price_from', IntegerType::class, ['label' => 'form.field.price_from'])
            ->add('price_to', IntegerType::class, ['label' => 'form.field.price_to'])
            ->add('year_from', IntegerType::class, ['label' => 'form.field.year_from'])
            ->add('year_to', IntegerType::class, ['label' => 'form.field.year_to'])

            ->add('fuel_type', EntityType::class, [
                'class' => FuelType::class,
                'label' => 'form.field.fuel_type',
                'placeholder' => 'form.placeholder.all.fuel_type',
                'query_builder' => function (EntityRepository $repo) {
                    return $repo->createQueryBuilder('fuel_type')->orderBy('fuel_type.name', 'ASC');
                },
                'required' => false,
            ])
            ->add('provider', TextType::class, ['label' => 'form.field.provider', ])
            ->add('country', EntityType::class, [
                'class' => Country::class,
                'label' => 'form.field.country',
                'placeholder' => 'form.placeholder.all.country',
                'query_builder' => function (EntityRepository $repo) {
                    return $repo->createQueryBuilder('country')->orderBy('country.name', 'ASC');
                },
                'required' => false,
            ])
            ->add('engine_size_from', IntegerType::class, ['label' => 'form.field.engine_size_from'])
            ->add('engine_size_to', IntegerType::class, ['label' => 'form.field.engine_size_to'])
            ->add('power_from', IntegerType::class, ['label' => 'form.field.power_from'])
            ->add('power_to', IntegerType::class, ['label' => 'form.field.power_to'])
            ->add('doors_number', IntegerType::class, ['label' => 'form.field.doors_number'])
            ->add('seats_number', IntegerType::class, ['label' => 'form.field.seats_number'])
            ->add('drive_type', TextType::class, ['label' => 'form.field.drive_type'])
            ->add('climate_control', TextType::class, ['label' => 'form.field.climate_control'])
            ->add('color', EntityType::class, [
                'class' => Color::class,
                'label' => 'form.field.color',
                'placeholder' => 'form.placeholder.all.color',
                'query_builder' => function (EntityRepository $repo) {
                    return $repo->createQueryBuilder('color')->orderBy('color.name', 'ASC');
                },
                'required' => false,
            ])
            ->add('defects', TextType::class, ['label' => 'form.field.defects'])
            ->add('steering_wheel', ChoiceType::class, [
                'choices' => [
                    'form.choice.steering_wheel.left' => 0,
                    'form.choice.steering_wheel.right' => 1,
                ],
                'data' => 0,
                'label' => 'form.field.steering_wheel',
                'placeholder' => 'form.placeholder.all.steering_wheel',
                'required' => false,
            ])
            ->add('wheelsDiameter', IntegerType::class, ['label' => 'form.field.wheels_diameter'])
            ->add('mileage_from', IntegerType::class, ['label' => 'form.field.mileage_from'])
            ->add('mileage_to', IntegerType::class, ['label' => 'form.field.mileage_to'])
            ->add('sort', ChoiceType::class, [
                'choices' => [
                    'form.choice.sort.cost_min' => 'cost_min',
                    'form.choice.sort.cost_max' => 'cost_max',
                    'form.choice.sort.date_new' => 'date_new',
                    'form.choice.sort.date_old' => 'date_old',
                ],
                'data' => 'cost_min',
                'label' => 'form.field.sort',
                'placeholder' => false,
                'required' => false,
            ]);

        $builder->addEventListener(
            FormEvents::POST_SET_DATA,
            function (FormEvent $event) use ($modelModifier) {
                $data = $event->getData();
                $brand = ($data === null) ? null : $data->getBrand();
                $modelModifier($event->getForm(), $brand);
            }
        );


        $builder->get('brand')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($modelModifier) {
                $brand = $event->getForm()->getData();
                $modelModifier($event->getForm()->getParent(), $brand);
            }
        );

        $builder->addEventListener(
            FormEvents::POST_SET_DATA,
            function (FormEvent $event) use ($cityModifier) {
                $data = $event->getData();
                $country = ($data === null) ? null : $data->getCountry();
                $cityModifier($event->getForm(), $country);
            }
        );


        $builder->get('country')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($cityModifier) {
                $country = $event->getForm()->getData();
                $cityModifier($event->getForm()->getParent(), $country);
            }
        );
    }
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'csrf_protection' => false,
        ));
    }

    public function getBlockPrefix()
    {
        return null;
    }
}
