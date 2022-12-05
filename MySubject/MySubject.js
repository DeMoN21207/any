class MySubject {

    store = [];
    next(val) {
        this.store.forEach(func => {
            func(val);
        })
    }

    subscribe(val) {
        this.store.push(val);
    }
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
