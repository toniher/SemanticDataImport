iris.csv originally from: http://archive.ics.uci.edu/ml/machine-learning-databases/iris/iris.data

Swap columns via and add header: 

    perl -MText::Trim -F, -ane 'if ( $.==1 ) { print "Species,Sepal Length,Sepal Width,Petal Length,Petal Width\n"; } if ($_=~/\S/) { print trim( $F[4]).",$F[0],$F[1],$F[2],$F[3]\n"; }' iris.data > iris.csv



