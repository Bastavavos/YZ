<?php

namespace App\Form;

use App\Entity\Post;
use Symfony\Component\DomCrawler\Image;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PostType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('content')
            ->add('picture', FileType::class, [
                'required' => false,
                'mapped' => false, //ne cherche pas de getter et setter
            ])
            ->add('tags')
            ->add('parent', HiddenType::class, [
                'required' => false, // champ optionnel
                'mapped' => false,  // pour ne pas lier directement ce champ à une propriété de l'entité Post
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Post::class,
        ]);
    }
}
