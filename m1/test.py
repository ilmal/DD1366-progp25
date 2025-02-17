def remove_every_nth(n: int, lst: list[int]) -> list[int]:
    if n <= 0:
        raise ValueError("n must be a positive integer")
    result = []
    for i in range(0, len(lst), n):
        result.extend(lst[i:i+n-1])
    return result


if __name__ == "__main__":
    original = list(range(1, 100000000))
    result = remove_every_nth(3, original)
    print(result)