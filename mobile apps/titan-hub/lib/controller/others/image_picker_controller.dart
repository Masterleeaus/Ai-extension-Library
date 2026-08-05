import 'dart:io';

import 'package:get/get.dart';
import 'package:image_picker/image_picker.dart';

File? pickedFile;
ImagePicker imagePicker = ImagePicker();

 

class ProfileImagePicker extends GetxController {
  var isImagePathSet = false.obs;
  var imagePath = "".obs;

  void setImagePath(String path) {
    imagePath.value = path;
    isImagePathSet.value = true;
  }
}
