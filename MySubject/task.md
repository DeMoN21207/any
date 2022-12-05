
Необходимо реализовать класс MySubject чтобы следующий код заработал


class MySubject {
        
    constructor() {}
    next() {}
    subscribe() {}

}

const t1 = new MySubject();

t1.subscribe((value) => {
console.log(value + 1);
});

t1.subscribe((value) => {
console.log(value + 3);
});

t1.next(1);
t1.next(2);